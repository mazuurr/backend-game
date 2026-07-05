<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Repository;

use App\Puzzle\Domain\Entity\PuzzleBoardPiece;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;

interface PuzzleBoardPieceRepositoryInterface
{
    public function save(PuzzleBoardPiece $piece): void;

    public function findBySessionAndPiece(PuzzleSessionId $sessionUuid, int $pieceIndex): ?PuzzleBoardPiece;

    /** @return PuzzleBoardPiece[] */
    public function findBySession(PuzzleSessionId $sessionUuid): array;
}
