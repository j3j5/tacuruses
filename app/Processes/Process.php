<?php

declare(strict_types=1);

namespace App\Processes;

use Illuminate\Pipeline\Pipeline;

abstract class Process
{
    protected array $tasks = [];

    public function run(object $payload): mixed
    {
        return (new Pipeline(app()))->send(
            $payload,
        )->through(
            $this->tasks,
        )->thenReturn();
    }
}
