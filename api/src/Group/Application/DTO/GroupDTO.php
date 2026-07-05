<?php

declare(strict_types=1);

namespace App\Group\Application\DTO;

use App\Group\Domain\Entity\Group;
use App\User\Application\DTO\UserDTO;

final class GroupDTO implements \JsonSerializable
{
    /** @param UserDTO[] $users */
    public function __construct(
        public readonly string $uuid,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $ownerUuid,
        public readonly string $createdAt,
        public readonly ?string $updatedAt,
        public readonly int $membersCount = 0,
        public readonly array $users = [],
    ) {}

    public static function fromEntity(Group $group, int $membersCount = 0, array $users = []): self
    {
        return new self(
            uuid: $group->getUuid()->value(),
            name: $group->getName()->value(),
            description: $group->getDescription(),
            ownerUuid: $group->getOwnerUuid()?->value(),
            createdAt: $group->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $group->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            membersCount: $membersCount,
            users: $users,
        );
    }

    public function jsonSerialize(): array
    {
        $data = [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'owner_uuid' => $this->ownerUuid,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'members_count' => $this->membersCount,
        ];

        if ($this->users !== []) {
            $data['users'] = $this->users;
        }

        return $data;
    }
}
