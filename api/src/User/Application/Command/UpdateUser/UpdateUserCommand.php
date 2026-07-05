<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateUser;

final class UpdateUserCommand
{
    public function __construct(
        public readonly string $uuid,
        public readonly ?string $username = null,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
    ) {}
}
