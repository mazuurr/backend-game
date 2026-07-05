<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Repository;

use App\Puzzle\Domain\Entity\PuzzlePieceMove;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;

interface PuzzlePieceMoveRepositoryInterface
{
    public function save(PuzzlePieceMove $move): void;

    public function nextSeq(PuzzleSessionId $sessionUuid): int;

    /** @return PuzzlePieceMove[] */
    public function findBySession(PuzzleSessionId $sessionUuid): array;
}
