<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Support\UserFactory;
use App\User\Application\Command\CreateUser\CreateUserCommand;
use App\User\Application\Command\CreateUser\CreateUserHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\EmailAlreadyExistsException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Mailer\ActivationMailerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(CreateUserHandler::class)]
final class CreateUserHandlerTest extends TestCase
{
    private UserRepositoryInterface&MockObject $repository;
    private ActivationMailerInterface&MockObject $mailer;
    private CreateUserHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->mailer = $this->createMock(ActivationMailerInterface::class);
        $this->handler = new CreateUserHandler($this->repository, $this->mailer);
    }

    public function testRegistersInactiveUserAndSendsActivationEmail(): void
    {
        $this->repository->method('emailExists')->willReturn(false);

        $saved = null;
        $this->repository->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (User $user) use (&$saved): void {
                $saved = $user;
            });

        $sentTo = null;
        $sentToken = null;
        $this->mailer->expects(self::once())
            ->method('sendActivationEmail')
            ->willReturnCallback(static function (string $email, string $token) use (&$sentTo, &$sentToken): void {
                $sentTo = $email;
                $sentToken = $token;
            });

        ($this->handler)(new CreateUserCommand('Jan.Kowalski@Example.com', UserFactory::PASSWORD));

        self::assertInstanceOf(User::class, $saved);
        self::assertFalse($saved->isActive());
        self::assertSame('jan.kowalski@example.com', $saved->getEmail()->value());
        self::assertSame('jan.kowalski@example.com', $sentTo);
        self::assertNotNull($sentToken);
    }

    public function testEmailIsCheckedInNormalizedForm(): void
    {
        $this->repository->expects(self::once())
            ->method('emailExists')
            ->with(self::callback(static fn ($email) => $email->value() === 'jan@example.com'))
            ->willReturn(false);

        ($this->handler)(new CreateUserCommand('JAN@EXAMPLE.COM', UserFactory::PASSWORD));
    }

    public function testRejectsDuplicateEmail(): void
    {
        $this->repository->method('emailExists')->willReturn(true);
        $this->repository->expects(self::never())->method('save');
        $this->mailer->expects(self::never())->method('sendActivationEmail');

        $this->expectException(EmailAlreadyExistsException::class);

        ($this->handler)(new CreateUserCommand('jan@example.com', UserFactory::PASSWORD));
    }

    public function testRejectsInvalidEmailBeforeTouchingTheRepository(): void
    {
        $this->repository->expects(self::never())->method('emailExists');

        $this->expectException(\InvalidArgumentException::class);

        ($this->handler)(new CreateUserCommand('nie-email', UserFactory::PASSWORD));
    }

    public function testRejectsTooShortPassword(): void
    {
        $this->repository->method('emailExists')->willReturn(false);
        $this->repository->expects(self::never())->method('save');

        $this->expectException(\InvalidArgumentException::class);

        ($this->handler)(new CreateUserCommand('jan@example.com', '1234567'));
    }

    public function testStoresPasswordHashedNotInPlainText(): void
    {
        $this->repository->method('emailExists')->willReturn(false);

        $saved = null;
        $this->repository->method('save')->willReturnCallback(static function (User $user) use (&$saved): void {
            $saved = $user;
        });

        ($this->handler)(new CreateUserCommand('jan@example.com', UserFactory::PASSWORD));

        self::assertInstanceOf(User::class, $saved);
        self::assertNotSame(UserFactory::PASSWORD, $saved->getPassword()->value());
        self::assertTrue($saved->getPassword()->verify(UserFactory::PASSWORD));
    }
}
