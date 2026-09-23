<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Repository;

use App\Puzzle\Domain\Entity\PuzzleSessionStat;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Shared\Domain\ValueObject\UserId;

interface PuzzleSessionStatRepositoryInterface
{
    /**
     * Atomic at the DB level (INSERT IGNORE) — safe against two concurrent
     * completions of the same session racing each other, unlike a
     * check-then-insert pair, which two requests could both pass before
     * either commits.
     */
    public function insertIfMissing(PuzzleSessionStat $stat): void;

    /** Whether this user has a fully-solved snapshot for this puzzle (any past session). */
    public function hasCompletedPuzzle(UserId $userUuid, PuzzleId $puzzleUuid): bool;

    /** @return PuzzleSessionStat[] */
    public function findPaginated(int $offset, int $limit): array;

    public function count(): int;

    /** @return PuzzleSessionStat[] */
    public function findPaginatedByUser(UserId $userUuid, int $offset, int $limit, ?PuzzleId $puzzleUuid = null): array;

    public function countByUser(UserId $userUuid, ?PuzzleId $puzzleUuid = null): int;
}
