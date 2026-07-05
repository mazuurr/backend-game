<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Mailer;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class SymfonyActivationMailer implements ActivationMailerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $activationBaseUrl,
        private readonly string $fromEmail,
    ) {}

    public function sendActivationEmail(string $email, string $activationToken): void
    {
        $activationUrl = sprintf('%s?token=%s', $this->activationBaseUrl, $activationToken);

        $message = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($email)
            ->subject('Aktywacja konta – Puzzel')
            ->htmlTemplate('emails/activation.html.twig')
            ->context([
                'activation_url' => $activationUrl,
            ]);

        $this->mailer->send($message);
    }
}
