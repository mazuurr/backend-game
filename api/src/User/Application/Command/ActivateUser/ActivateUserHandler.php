<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateUser;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ActivateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(ActivateUserCommand $command): void
    {
        $user = $this->userRepository->findByUuid(new UserId($command->uuid));

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $user->activateByAdmin();

        $this->userRepository->save($user);
    }
}
