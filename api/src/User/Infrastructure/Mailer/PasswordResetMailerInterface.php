<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Mailer;

interface PasswordResetMailerInterface
{
    public function sendPasswordResetEmail(string $email, string $resetToken): void;
}
