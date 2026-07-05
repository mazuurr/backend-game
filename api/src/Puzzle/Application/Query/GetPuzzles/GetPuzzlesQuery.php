<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetPuzzles;

final class GetPuzzlesQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly ?string $campaignUuid = null,
    ) {}
}
