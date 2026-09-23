<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateUserAdmin;

use App\User\Domain\Entity\User;
use App\User\Domain\Exception\EmailAlreadyExistsException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\HashedPassword;
use App\Shared\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\Username;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateUserAdminHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(CreateUserAdminCommand $command): void
    {
        $email = new Email($command->email);

        if ($this->userRepository->emailExists($email)) {
            throw new EmailAlreadyExistsException();
        }

        $user = User::createByAdmin(
            uuid: UserId::generate(),
            username: new Username($command->username),
            email: $email,
            password: HashedPassword::fromPlain($command->password),
            active: $command->active,
        );

        $this->userRepository->save($user);
    }
}
