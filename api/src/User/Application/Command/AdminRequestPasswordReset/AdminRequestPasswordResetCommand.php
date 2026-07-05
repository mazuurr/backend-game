<?php

declare(strict_types=1);

namespace App\User\Application\Command\AdminRequestPasswordReset;

final class AdminRequestPasswordResetCommand
{
    public function __construct(
        public readonly string $userUuid,
    ) {}
}
