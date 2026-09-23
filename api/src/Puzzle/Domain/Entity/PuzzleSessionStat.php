<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Entity;

use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\Shared\Domain\ValueObject\UserId;

/**
 * A permanent, aggregate-only snapshot of a session, captured right before the
 * session and its move log / board state are purged (see app:sessions:purge).
 * Keeps "how long" and "how much of the puzzle" without keeping the underlying
 * per-piece-move rows that make the session expensive to store at scale.
 */
class PuzzleSessionStat
{
    private int $id;
    private PuzzleSessionId $sessionUuid;
    private PuzzleId $puzzleUuid;
    private UserId $createdByUserUuid;
    private string $mode;
    private int $totalPieces;
    private int $correctPieces;
    private int $timeSpentSeconds;
    private \DateTimeImmutable $startedAt;
    private \DateTimeImmutable $finishedAt;
    private \DateTimeImmutable $recordedAt;

    private function __construct(
        PuzzleSessionId $sessionUuid,
        PuzzleId $puzzleUuid,
        UserId $createdByUserUuid,
        string $mode,
        int $totalPieces,
        int $correctPieces,
        \DateTimeImmutable $startedAt,
        \DateTimeImmutable $finishedAt,
    ) {
        $this->sessionUuid = $sessionUuid;
        $this->puzzleUuid = $puzzleUuid;
        $this->createdByUserUuid = $createdByUserUuid;
        $this->mode = $mode;
        $this->totalPieces = $totalPieces;
        $this->correctPieces = $correctPieces;
        $this->timeSpentSeconds = max(0, $finishedAt->getTimestamp() - $startedAt->getTimestamp());
        $this->startedAt = $startedAt;
        $this->finishedAt = $finishedAt;
        $this->recordedAt = new \DateTimeImmutable();
    }

    public static function capture(
        PuzzleSessionId $sessionUuid,
        PuzzleId $puzzleUuid,
        UserId $createdByUserUuid,
        string $mode,
        int $totalPieces,
        int $correctPieces,
        \DateTimeImmutable $startedAt,
        \DateTimeImmutable $finishedAt,
    ): self {
        return new self(
            $sessionUuid,
            $puzzleUuid,
            $createdByUserUuid,
            $mode,
            $totalPieces,
            $correctPieces,
            $startedAt,
            $finishedAt,
        );
    }

    public function getId(): int { return $this->id; }
    public function getSessionUuid(): PuzzleSessionId { return $this->sessionUuid; }
    public function getPuzzleUuid(): PuzzleId { return $this->puzzleUuid; }
    public function getCreatedByUserUuid(): UserId { return $this->createdByUserUuid; }
    public function getMode(): string { return $this->mode; }
    public function getTotalPieces(): int { return $this->totalPieces; }
    public function getCorrectPieces(): int { return $this->correctPieces; }
    public function getTimeSpentSeconds(): int { return $this->timeSpentSeconds; }
    public function getStartedAt(): \DateTimeImmutable { return $this->startedAt; }
    public function getFinishedAt(): \DateTimeImmutable { return $this->finishedAt; }
    public function getRecordedAt(): \DateTimeImmutable { return $this->recordedAt; }
}
