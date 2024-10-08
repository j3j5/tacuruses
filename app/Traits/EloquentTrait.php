<?php

declare(strict_types=1);

namespace App\Traits;

trait EloquentTrait
{
    /**
     *
     * @return array<int, string>
     */
    public function getTableColumns() : array
    {
        return $this->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($this->getTable());
    }
}
