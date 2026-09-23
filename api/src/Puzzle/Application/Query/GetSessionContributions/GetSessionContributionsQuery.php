<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetSessionContributions;

final class GetSessionContributionsQuery
{
    public function __construct(
        public readonly string $sessionUuid,
    ) {}
}
