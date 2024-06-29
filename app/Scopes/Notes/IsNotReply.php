<?php

declare(strict_types=1);

namespace App\Scopes\Notes;

use Illuminate\Database\Eloquent\Builder;

final readonly class IsNotReply
{
    public function __invoke(Builder $builder): void
    {
        $builder->whereNull('replyTo_id');
    }
}
