<?php

declare(strict_types=1);

namespace App\Scopes\Actors;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final readonly class IsActive
{
    private readonly Carbon $lastSeen;

    public function __construct(string|Carbon|null $lastSeen = null)
    {
        $this->lastSeen = match(true) {
            // Default
            is_null($lastSeen) => now()->subWeek(),
            is_string($lastSeen) => Carbon::parse($lastSeen),
            default => $lastSeen,
        };
    }

    public function __invoke(Builder $builder): void
    {
        $builder->whereHas('notes', function (Builder $query) {
            $query->where('published_at', '>=', $this->lastSeen);
        });
    }
}
