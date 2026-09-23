<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetMySessionStats;

final class GetMySessionStatsQuery
{
    public function __construct(
        public readonly string $requesterUserUuid,
        public readonly ?string $puzzleUuid = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
