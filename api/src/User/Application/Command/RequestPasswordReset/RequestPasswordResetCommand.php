<?php

declare(strict_types=1);

namespace App\User\Application\Command\RequestPasswordReset;

final class RequestPasswordResetCommand
{
    public function __construct(
        public readonly string $email,
    ) {}
}
