<?php

declare(strict_types=1);

namespace App\User\Application\Command\AdminRequestPasswordReset;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use App\User\Infrastructure\Mailer\PasswordResetMailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class AdminRequestPasswordResetHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordResetMailerInterface $passwordResetMailer,
    ) {}

    public function __invoke(AdminRequestPasswordResetCommand $command): void
    {
        $user = $this->userRepository->findByUuid(new UserId($command->userUuid));

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $token = $user->requestPasswordReset();
        $this->userRepository->save($user);

        $this->passwordResetMailer->sendPasswordResetEmail($user->getEmail()->value(), $token);
    }
}
