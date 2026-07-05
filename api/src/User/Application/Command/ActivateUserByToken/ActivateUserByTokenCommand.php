<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateUserByToken;

final class ActivateUserByTokenCommand
{
    public function __construct(
        public readonly string $token,
    ) {}
}
