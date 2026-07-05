<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetPuzzleProgress;

final class GetPuzzleProgressQuery
{
    public function __construct(
        public readonly string $puzzleUuid,
    ) {}
}
