<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class FederationConnectionException extends ConnectionException
{

    public function __construct(public string $url, ConnectionException $previous)
    {
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
    }

    /**
     * Report the exception.
     */
    public function report(): bool
    {
        if (app()->environment('production')) {
            Log::error($this->getMessage());
            // Stop the reporting here and don't go to the default exception handling reporting
            return true;
        }
        // use the default exception handling configuration
        return false;
    }

    /**
     * Get the exception's context information.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return ['url' => $this->url];
    }
}
