<?php

declare(strict_types=1);

namespace App\User\Application\Command\ChangePassword;

final class ChangePasswordCommand
{
    public function __construct(
        public readonly string $userUuid,
        public readonly string $currentPassword,
        public readonly string $newPassword,
    ) {}
}
