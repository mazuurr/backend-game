<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetSessionStats;

final class GetSessionStatsQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
