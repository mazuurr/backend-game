<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Mailer;

interface ActivationMailerInterface
{
    public function sendActivationEmail(string $email, string $activationToken): void;
}
