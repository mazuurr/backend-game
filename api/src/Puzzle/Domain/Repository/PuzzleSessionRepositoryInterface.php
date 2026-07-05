<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Repository;

use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;

interface PuzzleSessionRepositoryInterface
{
    public function save(PuzzleSession $session): void;

    public function findByUuid(PuzzleSessionId $uuid): ?PuzzleSession;

    /** @return PuzzleSession[] */
    public function findOpen(): array;

    /** @return PuzzleSession[] */
    public function findExpiredOpen(\DateTimeImmutable $now): array;
}
