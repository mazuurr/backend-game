<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\RecordProgress;

final class RecordProgressCommand
{
    public function __construct(
        public readonly string $userUuid,
        public readonly string $puzzleUuid,
        public readonly int $currentFragment,
        public readonly int $piecesPlaced,
        public readonly int $timeSpentSeconds,
        public readonly bool $completed = false,
    ) {}
}
