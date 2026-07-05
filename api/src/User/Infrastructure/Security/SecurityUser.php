<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getUserIdentifier(): string
    {
        return $this->user->getEmail()->value();
    }

    public function getUuid(): string
    {
        return $this->user->getUuid()->value();
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): string
    {
        return $this->user->getPassword()->value();
    }

    public function eraseCredentials(): void
    {
    }
}
