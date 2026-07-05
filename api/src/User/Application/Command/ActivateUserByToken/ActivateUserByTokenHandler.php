<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateUserByToken;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ActivateUserByTokenHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(ActivateUserByTokenCommand $command): void
    {
        $user = $this->userRepository->findByActivationToken($command->token);

        if ($user === null) {
            throw new UserNotFoundException('User with given activation token not found.');
        }

        $user->activateByToken($command->token);

        $this->userRepository->save($user);
    }
}
