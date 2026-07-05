<?php

declare(strict_types=1);

namespace App\Group\Domain\Entity;

use App\Group\Domain\ValueObject\GroupId;
use App\Group\Domain\ValueObject\GroupName;
use App\User\Domain\ValueObject\UserId;

class Group
{
    private int $id;
    private GroupId $uuid;
    private GroupName $name;
    private ?string $description;
    private ?UserId $ownerUuid;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct(
        GroupId $uuid,
        GroupName $name,
        ?string $description,
        ?UserId $ownerUuid,
    ) {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->description = $description;
        $this->ownerUuid = $ownerUuid;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = null;
    }

    public static function create(
        GroupId $uuid,
        GroupName $name,
        ?string $description = null,
        ?UserId $ownerUuid = null,
    ): self {
        return new self($uuid, $name, $description, $ownerUuid);
    }

    public function isOwnedBy(UserId $userId): bool
    {
        return $this->ownerUuid !== null && $this->ownerUuid->equals($userId);
    }

    public function transferOwnership(UserId $newOwnerUuid): void
    {
        $this->ownerUuid = $newOwnerUuid;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function update(
        ?GroupName $name,
        ?string $description,
        bool $clearDescription = false,
    ): void {
        if ($name !== null) {
            $this->name = $name;
        }
        if ($clearDescription) {
            $this->description = null;
        } elseif ($description !== null) {
            $this->description = $description;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): GroupId
    {
        return $this->uuid;
    }

    public function getName(): GroupName
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getOwnerUuid(): ?UserId
    {
        return $this->ownerUuid;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
