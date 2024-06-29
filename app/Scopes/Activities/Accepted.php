<?php

declare(strict_types=1);

namespace App\Scopes\Activities;

use Illuminate\Database\Eloquent\Builder;

final readonly class Accepted
{
    public function __construct(private readonly ?string $table = null)
    {

    }

    public function __invoke(Builder $builder): void
    {
        $column = 'accepted';
        if (is_string($this->table)) {
            $column = "{$this->table}.{$column}";
        }
        $builder->where($column, 1);
    }
}
