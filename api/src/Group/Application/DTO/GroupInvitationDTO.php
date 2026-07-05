<?php

declare(strict_types=1);

namespace App\Group\Application\DTO;

use App\Group\Domain\Entity\GroupInvitation;

final class GroupInvitationDTO implements \JsonSerializable
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $groupUuid,
        public readonly string $userUuid,
        public readonly string $type,
        public readonly string $status,
        public readonly string $initiatorUuid,
        public readonly string $createdAt,
        public readonly ?string $respondedAt,
    ) {}

    public static function fromEntity(GroupInvitation $invitation): self
    {
        return new self(
            uuid: $invitation->getUuid()->value(),
            groupUuid: $invitation->getGroupUuid()->value(),
            userUuid: $invitation->getUserUuid()->value(),
            type: $invitation->getType(),
            status: $invitation->getStatus(),
            initiatorUuid: $invitation->getInitiatorUuid()->value(),
            createdAt: $invitation->getCreatedAt()->format(\DateTimeInterface::ATOM),
            respondedAt: $invitation->getRespondedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'group_uuid' => $this->groupUuid,
            'user_uuid' => $this->userUuid,
            'type' => $this->type,
            'status' => $this->status,
            'initiator_uuid' => $this->initiatorUuid,
            'created_at' => $this->createdAt,
            'responded_at' => $this->respondedAt,
        ];
    }
}
