<?php

declare(strict_types=1);

namespace App\User\Application\Command\ChangePassword;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ChangePasswordHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(ChangePasswordCommand $command): void
    {
        $user = $this->userRepository->findByUuid(new UserId($command->userUuid));

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $user->changePassword(
            $command->currentPassword,
            HashedPassword::fromPlain($command->newPassword),
        );

        $this->userRepository->save($user);
    }
}
