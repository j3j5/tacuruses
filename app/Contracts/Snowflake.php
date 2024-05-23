<?php

declare(strict_types=1);

namespace App\Contracts;

interface Snowflake
{
    /**
     *
     * @return string
     */
    public function id();
}
