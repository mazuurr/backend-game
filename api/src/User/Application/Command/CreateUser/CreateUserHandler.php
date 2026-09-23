<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateUser;

use App\User\Domain\Entity\User;
use App\User\Domain\Exception\EmailAlreadyExistsException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\HashedPassword;
use App\Shared\Domain\ValueObject\UserId;
use App\User\Infrastructure\Mailer\ActivationMailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ActivationMailerInterface $activationMailer,
    ) {}

    public function __invoke(CreateUserCommand $command): void
    {
        $email = new Email($command->email);

        if ($this->userRepository->emailExists($email)) {
            throw new EmailAlreadyExistsException();
        }

        $user = User::registerFromApp(
            uuid: UserId::generate(),
            email: $email,
            password: HashedPassword::fromPlain($command->password),
        );

        $this->userRepository->save($user);

        // Wyślij mail aktywacyjny
        $events = $user->pullDomainEvents();
        foreach ($events as $event) {
            if ($event instanceof \App\User\Domain\Event\UserCreatedEvent) {
                $this->activationMailer->sendActivationEmail(
                    $event->email->value(),
                    $event->activationToken->value(),
                );
            }
        }
    }
}
