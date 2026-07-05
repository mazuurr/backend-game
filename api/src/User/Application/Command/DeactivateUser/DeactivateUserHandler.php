<?php

declare(strict_types=1);

namespace App\User\Application\Command\DeactivateUser;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeactivateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(DeactivateUserCommand $command): void
    {
        $user = $this->userRepository->findByUuid(new UserId($command->uuid));

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $user->deactivate();

        $this->userRepository->save($user);
    }
}
