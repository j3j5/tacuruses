<?php

namespace App\Services\ActivityPub;

use Illuminate\Support\Arr;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

use function Safe\parse_url;

/**
 * Originally based on the code from Pixelfed.
 *
 * Plenty of modifications to adapt to this project, use phpseclib3 instead of
 * openSSL and plenty of other things.
 * Anyway, I'm obviously standing on the shoulders of giants.
 *
 * @url https://github.com/pixelfed/pixelfed/blob/da90bf630a3485a556a26e310219768906121520/app/Util/ActivityPub/HttpSignature.php
 * @license GNU Affero General Public License v3.0
 *
 * Pixelfed code is based on code from aaronpk/Nautilus
 * @url https://github.com/aaronpk/Nautilus/blob/0e401660c3f629ef63ffb4e14e7597e0c4f589c2/app/ActivityPub/HTTPSignature.php
 * @license Apache License 2.0
 */
final class NewSigner
{
    public const DATE_FORMAT = 'D, d M Y H:i:s \G\M\T';

    private string $digestAlgo;
    private string $signingAlgo;
    private string $keyId;
    private PrivateKey $privateKey;

    public function __construct(
        PrivateKey|string|null $privateKey = null,
        string $signingAlgo = 'rsa',
        string $digestAlgo = 'sha256',
        string $keyId = '',
    ) {
        if ($privateKey !== null) {
            $this->setPrivateKey($privateKey);
        }
        $this->setSigningAlgo($signingAlgo);
        $this->setDigestAlgo($digestAlgo);
        $this->setKeyId($keyId);
    }

    public function setDigestAlgo(string $algo) : self
    {
        if (!in_array($algo, hash_algos())) {
            throw new RuntimeException('Unsupported hash algorithm for digest');
        }

        $this->digestAlgo = $algo;

        return $this;
    }

    public function setSigningAlgo(string $algo) : self
    {
        $this->signingAlgo = $algo;

        return $this;
    }

    public function setKeyId(string $keyId) : self
    {
        $this->keyId = $keyId;

        return $this;
    }

    /**
     *
     * @param string|\phpseclib3\Crypt\Common\PrivateKey $privateKey
     * @param string|null $password
     * @throws \RuntimeException
     * @throws \phpseclib3\Exception\NoKeyLoadedException
     * @return \App\Services\ActivityPub\NewSigner
     */
    public function setPrivateKey(string|PrivateKey $privateKey, string $password = null) : self
    {
        if (is_string($privateKey)) {
            /** @phpstan-ignore-next-line */
            $privateKey = PublicKeyLoader::load($privateKey, $password ?? false);
            // In case a public key is provided
            if (!$privateKey instanceof PrivateKey) {
                throw new RuntimeException('Invalid key provided');
            }
        }

        $this->privateKey = $privateKey;

        if ($this->privateKey instanceof RSA) {
            // Mastodon requires padding relaxed PKCS1 for RSA
            $this->privateKey = $this->privateKey->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        }

        return $this;
    }

    public function signRequest(RequestInterface $request) : RequestInterface
    {
        if (!isset($this->privateKey)) {
            throw new RuntimeException('Missing private key, cannot sign');
        }

        if (!isset($this->keyId)) {
            throw new RuntimeException('Missing KeyId, cannot sign');
        }

        $body = (string) $request->getBody();

        // Calculate digest, if needed
        $digest = null;
        if ($body !== null) {
            $digest = base64_encode(hash($this->digestAlgo, $body, true));
        }

        // Get headers to be signed
        $headersToSign = $this->headersToSign(
            url: $request->getUri(),
            digest: $digest,
            method: $request->getMethod(),
        );

        foreach($headersToSign as $name => $value) {
            if ($name === '(request-target)') {
                continue;
            }
            if (!$request->hasHeader($name)) {
                $request = $request->withHeader($name, $value);
            }
        }

        /** @var array<string, string[]> */
        $headers = [
            'date' => $request->getHeader('Date'),
            'host' => $request->getHeader('Host'),
            'content-type' => $request->getHeader('Content-Type'),
            'digest' => $request->getHeader('Digest'),
            'accept' => $request->getHeader('Accept'),
        ];

        $headers = collect($headers)
            // Flatten the internal array
            ->mapWithKeys(function (array $value, string $name) {
                return [$name => Arr::first($value)];
            })->filter()
            // Add the (request-target)
            ->prepend($headersToSign['(request-target)'], '(request-target)');

        $stringToSign = $headers
            ->map(fn (string $value, string $name) => mb_strtolower($name) . ': ' . $value)
            ->implode("\n");

        $signedHeaders = $headers->keys()
            ->map(fn (string $key) => mb_strtolower($key))
            ->implode(' ');

        $signature = base64_encode(
            $this->privateKey->sign($stringToSign)
        );
        $signatureHeader = 'keyId="' . $this->keyId . '",';
        $signatureHeader .= 'headers="' . $signedHeaders . '",';
        $signatureHeader .= 'algorithm="' . $this->getAlgorithm() . '",';
        $signatureHeader .= 'signature="' . $signature . '"';

        $request = $request->withHeader('Signature', $signatureHeader);

        return $request;
    }

    private function headersToSign(string $url, ?string $digest = null, string $method = 'post') : array
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path)) {
            throw new RuntimeException('URL does not have a valid path: ' . $url);
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host)) {
            throw new RuntimeException('URL does not have a valid host: ' . $url);
        }

        $headers = [
            '(request-target)' => mb_strtolower($method) . ' ' . $path,
            'Date' => now('UTC')->format(self::DATE_FORMAT),
            'Host' => $host,
            'Content-Type' => 'application/activity+json',
        ];

        if ($digest !== null) {
            $headers['Digest'] = $this->getDigestAlgoString() . '=' . $digest;
        }

        return $headers;
    }

    private function getAlgorithm() : string
    {
        return match($this->signingAlgo) {
            'hs2019' => 'hs2019', // See https://datatracker.ietf.org/doc/id/draft-richanna-http-message-signatures-00.html#name-hs2019
            default => mb_strtolower($this->signingAlgo) . '-' . mb_strtolower($this->digestAlgo),
        };
    }

    private function getDigestAlgoString() : string
    {
        return match($this->digestAlgo) {
            'sha256' => 'SHA-256',
            'sha384' => 'SHA-384',
            'sha512' => 'SHA-512',
            default => mb_strtoupper($this->digestAlgo)
        };
    }
}
