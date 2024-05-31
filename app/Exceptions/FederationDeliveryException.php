<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class FederationDeliveryException extends RequestException
{
    /**
     * Report the exception.
     */
    public function report(): bool
    {
        if (app()->environment('production')) {
            Log::error($this->getMessage());
            Log::debug('Full body: ' . $this->response->body());
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
        return ['url' => $this->response->effectiveUri()];
    }
}
