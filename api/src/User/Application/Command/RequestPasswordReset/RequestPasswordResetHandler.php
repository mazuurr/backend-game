<?php

declare(strict_types=1);

namespace App\User\Application\Command\RequestPasswordReset;

use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Infrastructure\Mailer\PasswordResetMailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RequestPasswordResetHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordResetMailerInterface $passwordResetMailer,
    ) {}

    public function __invoke(RequestPasswordResetCommand $command): void
    {
        $user = $this->userRepository->findByEmail(new Email($command->email));

        // Celowo nie rzucamy wyjątku gdy email nie istnieje — zapobiega enumeracji kont
        if ($user === null) {
            return;
        }

        $token = $user->requestPasswordReset();
        $this->userRepository->save($user);

        $this->passwordResetMailer->sendPasswordResetEmail($user->getEmail()->value(), $token);
    }
}
