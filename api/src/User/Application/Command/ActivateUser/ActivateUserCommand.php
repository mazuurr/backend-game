<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateUser;

final class ActivateUserCommand
{
    public function __construct(
        public readonly string $uuid,
    ) {}
}
