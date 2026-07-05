<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\RecordPieceMove;

final class RecordPieceMoveCommand
{
    public function __construct(
        public readonly string $sessionUuid,
        public readonly string $userUuid,
        public readonly int $pieceIndex,
        public readonly int $toX,
        public readonly int $toY,
        public readonly bool $correct,
    ) {}
}
