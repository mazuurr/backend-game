<?php

declare(strict_types=1);

namespace App\Puzzle\Application\DTO;

final class UserContributionDTO implements \JsonSerializable
{
    public function __construct(
        public readonly string $userUuid,
        public readonly string $username,
        // Pieces the player currently has correctly placed (latest move per piece).
        public readonly int $correctCount,
        // All drops the player made in the session (append-only log).
        public readonly int $totalMoves,
        // Drops the player flagged correct at the time (regardless of later moves).
        public readonly int $correctMoves,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'user_uuid' => $this->userUuid,
            'username' => $this->username,
            'correct_count' => $this->correctCount,
            'total_moves' => $this->totalMoves,
            'correct_moves' => $this->correctMoves,
        ];
    }
}
