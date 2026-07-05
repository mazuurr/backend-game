<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Repository;

use App\Puzzle\Domain\Entity\PuzzleProgress;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\User\Domain\ValueObject\UserId;

interface PuzzleProgressRepositoryInterface
{
    public function save(PuzzleProgress $progress): void;

    public function findByUserAndPuzzle(UserId $userUuid, PuzzleId $puzzleUuid): ?PuzzleProgress;

    /** @return PuzzleProgress[] */
    public function findByUser(UserId $userUuid): array;

    /** @return PuzzleProgress[] */
    public function findByPuzzle(PuzzleId $puzzleUuid): array;
}
