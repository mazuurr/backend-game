<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class ApiAdminUser implements UserInterface
{
    public function getUserIdentifier(): string
    {
        return 'api_admin';
    }

    public function getRoles(): array
    {
        return ['ROLE_ADMIN'];
    }

    public function eraseCredentials(): void
    {
    }
}
