<?php

declare(strict_types=1);

namespace App\User\Application\Command\ResetPasswordByToken;

use App\User\Domain\Exception\InvalidResetTokenException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\HashedPassword;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ResetPasswordByTokenHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(ResetPasswordByTokenCommand $command): void
    {
        $user = $this->userRepository->findByResetToken($command->token);

        if ($user === null) {
            throw new InvalidResetTokenException();
        }

        $user->resetPasswordByToken(
            $command->token,
            HashedPassword::fromPlain($command->newPassword),
        );

        $this->userRepository->save($user);
    }
}
