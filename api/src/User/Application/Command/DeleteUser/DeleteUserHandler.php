<?php

declare(strict_types=1);

namespace App\User\Application\Command\DeleteUser;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $this->userRepository->findByUuid(new UserId($command->uuid));

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $this->userRepository->remove($user);
    }
}
