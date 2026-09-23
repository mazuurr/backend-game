<?php

declare(strict_types=1);

namespace App\Puzzle\Application\DTO;

use App\Puzzle\Domain\Entity\PuzzleSessionStat;

final class SessionStatDTO implements \JsonSerializable
{
    public function __construct(
        public readonly string $sessionUuid,
        public readonly string $puzzleUuid,
        public readonly string $createdByUserUuid,
        public readonly string $mode,
        public readonly int $totalPieces,
        public readonly int $correctPieces,
        public readonly int $timeSpentSeconds,
        public readonly string $startedAt,
        public readonly string $finishedAt,
        public readonly string $recordedAt,
    ) {}

    public static function fromEntity(PuzzleSessionStat $stat): self
    {
        return new self(
            sessionUuid: $stat->getSessionUuid()->value(),
            puzzleUuid: $stat->getPuzzleUuid()->value(),
            createdByUserUuid: $stat->getCreatedByUserUuid()->value(),
            mode: $stat->getMode(),
            totalPieces: $stat->getTotalPieces(),
            correctPieces: $stat->getCorrectPieces(),
            timeSpentSeconds: $stat->getTimeSpentSeconds(),
            startedAt: $stat->getStartedAt()->format(\DateTimeInterface::ATOM),
            finishedAt: $stat->getFinishedAt()->format(\DateTimeInterface::ATOM),
            recordedAt: $stat->getRecordedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'session_uuid' => $this->sessionUuid,
            'puzzle_uuid' => $this->puzzleUuid,
            'created_by_user_uuid' => $this->createdByUserUuid,
            'mode' => $this->mode,
            'total_pieces' => $this->totalPieces,
            'correct_pieces' => $this->correctPieces,
            'time_spent_seconds' => $this->timeSpentSeconds,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'recorded_at' => $this->recordedAt,
        ];
    }
}
