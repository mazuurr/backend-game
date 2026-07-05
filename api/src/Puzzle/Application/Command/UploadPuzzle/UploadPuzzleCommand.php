<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\UploadPuzzle;

final class UploadPuzzleCommand
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $originalName,
        public readonly string $tmpPath,
        public readonly string $mimeType,
        public readonly int $size,
        public readonly ?int $difficulty = null,
        public readonly ?int $totalPieces = null,
        public readonly ?int $piecesPerFragment = null,
        public readonly ?int $piecesX = null,
        public readonly ?int $piecesY = null,
    ) {}
}
