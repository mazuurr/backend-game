<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Entity;

use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\User\Domain\ValueObject\UserId;

class PuzzleProgress
{
    private int $id;
    private UserId $userUuid;
    private PuzzleId $puzzleUuid;
    private int $currentFragment;
    private int $piecesPlaced;
    private int $timeSpentSeconds;
    private bool $completed;
    private \DateTimeImmutable $startedAt;
    private ?\DateTimeImmutable $completedAt;
    private \DateTimeImmutable $updatedAt;

    private function __construct(UserId $userUuid, PuzzleId $puzzleUuid)
    {
        $this->userUuid = $userUuid;
        $this->puzzleUuid = $puzzleUuid;
        $this->currentFragment = 1;
        $this->piecesPlaced = 0;
        $this->timeSpentSeconds = 0;
        $this->completed = false;
        $this->completedAt = null;
        $this->startedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function start(UserId $userUuid, PuzzleId $puzzleUuid): self
    {
        return new self($userUuid, $puzzleUuid);
    }

    public function record(
        int $currentFragment,
        int $piecesPlaced,
        int $timeSpentSeconds,
        bool $completed,
        ?int $piecesPerFragment,
    ): void {
        $this->currentFragment = $currentFragment;
        $this->piecesPlaced = $piecesPlaced;
        $this->timeSpentSeconds = $timeSpentSeconds;
        $this->updatedAt = new \DateTimeImmutable();

        $fragmentComplete = $piecesPerFragment !== null && $piecesPlaced >= $piecesPerFragment;

        if ($completed || $fragmentComplete) {
            if (!$this->completed) {
                $this->completed = true;
                $this->completedAt = new \DateTimeImmutable();
            }
        }
    }

    public function getId(): int { return $this->id; }
    public function getUserUuid(): UserId { return $this->userUuid; }
    public function getPuzzleUuid(): PuzzleId { return $this->puzzleUuid; }
    public function getCurrentFragment(): int { return $this->currentFragment; }
    public function getPiecesPlaced(): int { return $this->piecesPlaced; }
    public function getTimeSpentSeconds(): int { return $this->timeSpentSeconds; }
    public function isCompleted(): bool { return $this->completed; }
    public function getStartedAt(): \DateTimeImmutable { return $this->startedAt; }
    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
