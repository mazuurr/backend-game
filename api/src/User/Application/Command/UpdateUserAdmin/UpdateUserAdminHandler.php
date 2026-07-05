<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateUserAdmin;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\Username;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateUserAdminHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(UpdateUserAdminCommand $command): void
    {
        $user = $this->userRepository->findByUuid(new UserId($command->uuid));

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $user->updateByAdmin(
            username: $command->username !== null ? new Username($command->username) : null,
            email: $command->email !== null ? new Email($command->email) : null,
            password: $command->password !== null ? HashedPassword::fromPlain($command->password) : null,
            premium: $command->premium,
            active: $command->active,
        );

        $this->userRepository->save($user);
    }
}
