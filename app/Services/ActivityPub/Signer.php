<?php

namespace App\Services\ActivityPub;

use App\Domain\ActivityPub\Contracts\Actor;
use App\Domain\ActivityPub\Contracts\Signer as ContractsSigner;
use RuntimeException;

use function Safe\openssl_pkey_get_private;
use function Safe\openssl_sign;
use function Safe\parse_url;

class Signer implements ContractsSigner
{
    public function sign(Actor $user, string $url, ?string $body = null, array $extraHeaders = []) : array
    {
        $digest = null;
        if ($body !== null) {
            $digest = base64_encode(hash('sha256', $body, true));
        }
        $headers = $this->headersToSign($url, $digest);
        $headers = array_merge($headers, $extraHeaders);
        $stringToSign = $this->stringFromHeaders($headers);
        $signedHeaders = implode(
            ' ',
            array_map('strtolower', array_keys($headers))
        );
        $key = openssl_pkey_get_private($user->privateKey);
        openssl_sign($stringToSign, $signature, $key, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);
        $signatureHeader = 'keyId="' . $user->keyId . '",headers="' . $signedHeaders . '",algorithm="rsa-sha256",signature="' . $signature . '"';
        unset($headers['(request-target)']);
        $headers['Signature'] = $signatureHeader;

        return $headers;
    }

    private function headersToSign(string $url, ?string $digest = null) : array
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path)) {
            throw new RuntimeException('URL does not have a valid path: ' . $url);
        }

        $headers = [
            '(request-target)' => 'post ' . $path,
            'Date' => now('UTC')->format('D, d M Y H:i:s \G\M\T'),
            'Host' => parse_url($url, PHP_URL_HOST),
            'Content-Type' => 'application/activity+json',
        ];

        if ($digest !== null) {
            $headers['Digest'] = 'SHA-256=' . $digest;
        }

        return $headers;
    }

    private function stringFromHeaders(array $headers) : string
    {
        return implode("\n", array_map(function ($k, $v) {
            return strtolower($k) . ': ' . $v;
        }, array_keys($headers), $headers));
    }
}
