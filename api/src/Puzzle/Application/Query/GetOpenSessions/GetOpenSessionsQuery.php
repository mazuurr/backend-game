<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetOpenSessions;

final class GetOpenSessionsQuery
{
    public function __construct(
        public readonly string $requesterUserUuid,
    ) {}
}
