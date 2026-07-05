<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetSessionMoves;

final class GetSessionMovesQuery
{
    public function __construct(
        public readonly string $sessionUuid,
    ) {}
}
