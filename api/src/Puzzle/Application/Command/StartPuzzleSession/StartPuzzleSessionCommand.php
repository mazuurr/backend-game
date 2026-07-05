<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\StartPuzzleSession;

final class StartPuzzleSessionCommand
{
    public function __construct(
        public readonly string $sessionUuid,
        public readonly string $puzzleUuid,
        public readonly string $createdByUserUuid,
        public readonly string $visibility,
        public readonly ?string $groupUuid = null,
    ) {}
}
