<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use App\User\Domain\Entity\User;

final class UserDTO implements \JsonSerializable
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $username,
        public readonly string $email,
        public readonly bool $active,
        public readonly string $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            uuid: $user->getUuid()->value(),
            username: $user->getUsername()->value(),
            email: $user->getEmail()->value(),
            active: $user->isActive(),
            createdAt: $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $user->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'username' => $this->username,
            'email' => $this->email,
            'active' => $this->active,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
