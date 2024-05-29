<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class FederationConnectionException extends ConnectionException
{

    public function __construct(public string $url)
    {
        //
    }

    /**
     * Report the exception.
     */
    public function report(): bool
    {
        if (app()->environment('production')) {
            Log::error($this->getMessage());

            return false;
        }

        return true;
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
