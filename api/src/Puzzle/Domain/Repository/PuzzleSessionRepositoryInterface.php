<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Repository;

use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\Shared\Domain\ValueObject\UserId;

interface PuzzleSessionRepositoryInterface
{
    public function save(PuzzleSession $session): void;

    public function delete(PuzzleSession $session): void;

    public function findByUuid(PuzzleSessionId $uuid): ?PuzzleSession;

    /** @return PuzzleSession[] */
    public function findOpen(): array;

    /** @return PuzzleSession[] Sessions created by, or played in (via a piece move), the given user; newest first. */
    public function findByUser(UserId $userUuid, ?string $mode = null, ?string $status = null): array;

    /** @return PuzzleSession[] */
    public function findExpiredOpen(\DateTimeImmutable $now): array;

    /** @return PuzzleSession[] Closed sessions closed before $threshold, ready to be purged. */
    public function findClosedBefore(\DateTimeImmutable $threshold): array;

    /** @return PuzzleSession[] */
    public function findPaginated(int $offset, int $limit, ?string $status): array;

    public function countFiltered(?string $status): int;
}
