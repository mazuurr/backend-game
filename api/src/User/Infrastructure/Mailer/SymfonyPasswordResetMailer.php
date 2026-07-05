<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Mailer;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class SymfonyPasswordResetMailer implements PasswordResetMailerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $passwordResetBaseUrl,
        private readonly string $fromEmail,
    ) {}

    public function sendPasswordResetEmail(string $email, string $resetToken): void
    {
        $resetUrl = sprintf('%s?token=%s', $this->passwordResetBaseUrl, $resetToken);

        $message = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($email)
            ->subject('Reset hasła – Puzzel')
            ->htmlTemplate('emails/password_reset.html.twig')
            ->context([
                'reset_url' => $resetUrl,
            ]);

        $this->mailer->send($message);
    }
}
