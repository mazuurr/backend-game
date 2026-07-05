<?php

declare(strict_types=1);

namespace App\User\Application\Command\ResetPasswordByToken;

final class ResetPasswordByTokenCommand
{
    public function __construct(
        public readonly string $token,
        public readonly string $newPassword,
    ) {}
}
