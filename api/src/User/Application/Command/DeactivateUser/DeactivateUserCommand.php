<?php

declare(strict_types=1);

namespace App\User\Application\Command\DeactivateUser;

final class DeactivateUserCommand
{
    public function __construct(
        public readonly string $uuid,
    ) {}
}
