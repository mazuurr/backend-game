<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\UpdatePuzzle;

final class UpdatePuzzleCommand
{
    public function __construct(
        public readonly string $uuid,
        public readonly bool $hasDifficulty = false,
        public readonly ?int $difficulty = null,
        public readonly bool $hasTotalPieces = false,
        public readonly ?int $totalPieces = null,
        public readonly bool $hasPiecesPerFragment = false,
        public readonly ?int $piecesPerFragment = null,
        public readonly bool $hasPiecesX = false,
        public readonly ?int $piecesX = null,
        public readonly bool $hasPiecesY = false,
        public readonly ?int $piecesY = null,
    ) {}
}
