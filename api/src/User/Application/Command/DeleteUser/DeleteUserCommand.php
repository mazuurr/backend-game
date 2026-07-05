<?php

declare(strict_types=1);

namespace App\User\Application\Command\DeleteUser;

final class DeleteUserCommand
{
    public function __construct(
        public readonly string $uuid,
    ) {}
}
