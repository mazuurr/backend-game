<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetPuzzle;

final class GetPuzzleQuery
{
    public function __construct(
        public readonly string $uuid,
    ) {}
}
