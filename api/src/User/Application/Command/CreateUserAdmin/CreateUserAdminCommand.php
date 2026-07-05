<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateUserAdmin;

final class CreateUserAdminCommand
{
    public function __construct(
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
        public readonly bool $premium = false,
        public readonly bool $active = true,
    ) {}
}
