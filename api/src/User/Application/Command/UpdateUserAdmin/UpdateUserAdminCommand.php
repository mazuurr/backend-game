<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateUserAdmin;

final class UpdateUserAdminCommand
{
    public function __construct(
        public readonly string $uuid,
        public readonly ?string $username = null,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
        public readonly ?bool $active = null,
    ) {}
}
