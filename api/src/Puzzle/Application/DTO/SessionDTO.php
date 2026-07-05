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
        public readonly ?string $groupUuid,
        public readonly string $createdByUserUuid,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly string $expiresAt,
        public readonly ?string $closedAt,
    ) {}

    public static function fromEntity(PuzzleSession $session): self
    {
        return new self(
            uuid: $session->getUuid()->value(),
            puzzleUuid: $session->getPuzzleUuid()->value(),
            visibility: $session->getVisibility(),
            groupUuid: $session->getGroupUuid()?->value(),
            createdByUserUuid: $session->getCreatedByUserUuid()->value(),
            status: $session->getStatus(),
            createdAt: $session->getCreatedAt()->format(\DateTimeInterface::ATOM),
            expiresAt: $session->getExpiresAt()->format(\DateTimeInterface::ATOM),
            closedAt: $session->getClosedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'puzzle_uuid' => $this->puzzleUuid,
            'visibility' => $this->visibility,
            'group_uuid' => $this->groupUuid,
            'created_by_user_uuid' => $this->createdByUserUuid,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'expires_at' => $this->expiresAt,
            'closed_at' => $this->closedAt,
        ];
    }
}
