<?php

declare(strict_types=1);

namespace App\Scopes\Notes;

use Illuminate\Database\Eloquent\Builder;

final readonly class Published
{
    public function __invoke(Builder $builder): void
    {
        $builder->whereNotNull('published_at');
    }
}
