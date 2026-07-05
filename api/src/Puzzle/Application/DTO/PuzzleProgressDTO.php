<?php

declare(strict_types=1);

namespace App\Puzzle\Application\DTO;

use App\Puzzle\Domain\Entity\PuzzleProgress;

final class PuzzleProgressDTO implements \JsonSerializable
{
    public function __construct(
        public readonly string $userUuid,
        public readonly string $puzzleUuid,
        public readonly int $currentFragment,
        public readonly int $piecesPlaced,
        public readonly ?int $piecesPerFragment,
        public readonly ?float $fragmentPercent,
        public readonly int $timeSpentSeconds,
        public readonly bool $completed,
        public readonly string $startedAt,
        public readonly ?string $completedAt,
        public readonly string $updatedAt,
    ) {}

    public static function fromEntity(PuzzleProgress $progress, ?int $piecesPerFragment): self
    {
        $fragmentPercent = $piecesPerFragment !== null && $piecesPerFragment > 0
            ? round($progress->getPiecesPlaced() / $piecesPerFragment * 100, 1)
            : null;

        return new self(
            userUuid: $progress->getUserUuid()->value(),
            puzzleUuid: $progress->getPuzzleUuid()->value(),
            currentFragment: $progress->getCurrentFragment(),
            piecesPlaced: $progress->getPiecesPlaced(),
            piecesPerFragment: $piecesPerFragment,
            fragmentPercent: $fragmentPercent,
            timeSpentSeconds: $progress->getTimeSpentSeconds(),
            completed: $progress->isCompleted(),
            startedAt: $progress->getStartedAt()->format(\DateTimeInterface::ATOM),
            completedAt: $progress->getCompletedAt()?->format(\DateTimeInterface::ATOM),
            updatedAt: $progress->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'user_uuid' => $this->userUuid,
            'puzzle_uuid' => $this->puzzleUuid,
            'current_fragment' => $this->currentFragment,
            'pieces_placed' => $this->piecesPlaced,
            'pieces_per_fragment' => $this->piecesPerFragment,
            'fragment_percent' => $this->fragmentPercent,
            'time_spent_seconds' => $this->timeSpentSeconds,
            'completed' => $this->completed,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
