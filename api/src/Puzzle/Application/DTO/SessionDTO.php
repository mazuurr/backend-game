<?php

declare(strict_types=1);

namespace App\Puzzle\Application\DTO;

use App\Puzzle\Domain\Entity\PuzzleSession;

final class SessionDTO implements \JsonSerializable
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $puzzleUuid,
        public readonly string $visibility,
        public readonly string $mode,
        public readonly string $createdByUserUuid,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly string $expiresAt,
        public readonly ?string $closedAt,
        public readonly ?int $totalPieces = null,
        public readonly ?int $correctPieces = null,
        public readonly ?UserContributionDTO $leader = null,
    ) {}

    public static function fromEntity(PuzzleSession $session): self
    {
        return new self(
            uuid: $session->getUuid()->value(),
            puzzleUuid: $session->getPuzzleUuid()->value(),
            visibility: $session->getVisibility(),
            mode: $session->getMode(),
            createdByUserUuid: $session->getCreatedByUserUuid()->value(),
            status: $session->getStatus(),
            createdAt: $session->getCreatedAt()->format(\DateTimeInterface::ATOM),
            expiresAt: $session->getExpiresAt()->format(\DateTimeInterface::ATOM),
            closedAt: $session->getClosedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    public static function fromEntityWithContributions(
        PuzzleSession $session,
        SessionContributionsDTO $contributions,
    ): self {
        return new self(
            uuid: $session->getUuid()->value(),
            puzzleUuid: $session->getPuzzleUuid()->value(),
            visibility: $session->getVisibility(),
            mode: $session->getMode(),
            createdByUserUuid: $session->getCreatedByUserUuid()->value(),
            status: $session->getStatus(),
            createdAt: $session->getCreatedAt()->format(\DateTimeInterface::ATOM),
            expiresAt: $session->getExpiresAt()->format(\DateTimeInterface::ATOM),
            closedAt: $session->getClosedAt()?->format(\DateTimeInterface::ATOM),
            totalPieces: $contributions->total,
            correctPieces: $contributions->correct,
            leader: $contributions->leader(),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'puzzle_uuid' => $this->puzzleUuid,
            'visibility' => $this->visibility,
            'mode' => $this->mode,
            'created_by_user_uuid' => $this->createdByUserUuid,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'expires_at' => $this->expiresAt,
            'closed_at' => $this->closedAt,
            'total_pieces' => $this->totalPieces,
            'correct_pieces' => $this->correctPieces,
            'leader' => $this->leader,
        ];
    }
}
