<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Support\UserFactory;
use App\User\Application\Command\AdminRequestPasswordReset\AdminRequestPasswordResetCommand;
use App\User\Application\Command\AdminRequestPasswordReset\AdminRequestPasswordResetHandler;
use App\User\Application\Command\ChangePassword\ChangePasswordCommand;
use App\User\Application\Command\ChangePassword\ChangePasswordHandler;
use App\User\Application\Command\RequestPasswordReset\RequestPasswordResetCommand;
use App\User\Application\Command\RequestPasswordReset\RequestPasswordResetHandler;
use App\User\Application\Command\ResetPasswordByToken\ResetPasswordByTokenCommand;
use App\User\Application\Command\ResetPasswordByToken\ResetPasswordByTokenHandler;
use App\User\Domain\Exception\InvalidCurrentPasswordException;
use App\User\Domain\Exception\InvalidResetTokenException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\UserId;
use App\User\Infrastructure\Mailer\PasswordResetMailerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChangePasswordHandler::class)]
#[CoversClass(RequestPasswordResetHandler::class)]
#[CoversClass(AdminRequestPasswordResetHandler::class)]
#[CoversClass(ResetPasswordByTokenHandler::class)]
final class PasswordHandlersTest extends TestCase
{
    private UserRepositoryInterface&MockObject $repository;
    private PasswordResetMailerInterface&MockObject $mailer;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->mailer = $this->createMock(PasswordResetMailerInterface::class);
    }

    // --- ChangePassword ---

    public function testChangePassword(): void
    {
        $user = UserFactory::active();
        $this->repository->method('findByUuid')->willReturn($user);
        $this->repository->expects(self::once())->method('save')->with($user);

        (new ChangePasswordHandler($this->repository))(new ChangePasswordCommand(
            userUuid: UserId::generate()->value(),
            currentPassword: UserFactory::PASSWORD,
            newPassword: 'nowe-haslo',
        ));

        self::assertTrue($user->getPassword()->verify('nowe-haslo'));
    }

    public function testChangePasswordRejectsWrongCurrentPassword(): void
    {
        $user = UserFactory::active();
        $this->repository->method('findByUuid')->willReturn($user);
        $this->repository->expects(self::never())->method('save');

        $this->expectException(InvalidCurrentPasswordException::class);

        (new ChangePasswordHandler($this->repository))(new ChangePasswordCommand(
            userUuid: UserId::generate()->value(),
            currentPassword: 'zle-haslo',
            newPassword: 'nowe-haslo',
        ));
    }

    public function testChangePasswordRejectsTooShortNewPassword(): void
    {
        $this->repository->method('findByUuid')->willReturn(UserFactory::active());
        $this->repository->expects(self::never())->method('save');

        $this->expectException(\InvalidArgumentException::class);

        (new ChangePasswordHandler($this->repository))(new ChangePasswordCommand(
            userUuid: UserId::generate()->value(),
            currentPassword: UserFactory::PASSWORD,
            newPassword: 'krotkie',
        ));
    }

    public function testChangePasswordThrowsWhenUserMissing(): void
    {
        $this->repository->method('findByUuid')->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        (new ChangePasswordHandler($this->repository))(new ChangePasswordCommand(
            UserId::generate()->value(),
            UserFactory::PASSWORD,
            'nowe-haslo',
        ));
    }

    // --- RequestPasswordReset (publiczny) ---

    public function testRequestPasswordResetIssuesTokenAndMailsIt(): void
    {
        $user = UserFactory::active(email: 'kamil@example.com');
        $this->repository->method('findByEmail')->willReturn($user);
        $this->repository->expects(self::once())->method('save')->with($user);

        $this->mailer->expects(self::once())
            ->method('sendPasswordResetEmail')
            ->with('kamil@example.com', self::callback(
                static fn (string $token) => $token !== '' && $token === $user->getResetToken(),
            ));

        (new RequestPasswordResetHandler($this->repository, $this->mailer))(
            new RequestPasswordResetCommand('kamil@example.com'),
        );
    }

    public function testRequestPasswordResetStaysSilentForUnknownEmail(): void
    {
        // Deliberate: revealing "no such user" would allow account enumeration.
        $this->repository->method('findByEmail')->willReturn(null);
        $this->repository->expects(self::never())->method('save');
        $this->mailer->expects(self::never())->method('sendPasswordResetEmail');

        (new RequestPasswordResetHandler($this->repository, $this->mailer))(
            new RequestPasswordResetCommand('nieznany@example.com'),
        );

        $this->addToAssertionCount(1);
    }

    // --- AdminRequestPasswordReset ---

    public function testAdminRequestPasswordResetMailsTheToken(): void
    {
        $user = UserFactory::active(email: 'kamil@example.com');
        $this->repository->method('findByUuid')->willReturn($user);
        $this->repository->expects(self::once())->method('save')->with($user);
        $this->mailer->expects(self::once())->method('sendPasswordResetEmail')->with('kamil@example.com', self::isString());

        (new AdminRequestPasswordResetHandler($this->repository, $this->mailer))(
            new AdminRequestPasswordResetCommand(UserId::generate()->value()),
        );

        self::assertNotNull($user->getResetToken());
    }

    public function testAdminRequestPasswordResetThrowsForUnknownUser(): void
    {
        $this->repository->method('findByUuid')->willReturn(null);
        $this->mailer->expects(self::never())->method('sendPasswordResetEmail');

        $this->expectException(UserNotFoundException::class);

        (new AdminRequestPasswordResetHandler($this->repository, $this->mailer))(
            new AdminRequestPasswordResetCommand(UserId::generate()->value()),
        );
    }

    // --- ResetPasswordByToken ---

    public function testResetPasswordByToken(): void
    {
        $user = UserFactory::active();
        $token = $user->requestPasswordReset();

        $this->repository->expects(self::once())->method('findByResetToken')->with($token)->willReturn($user);
        $this->repository->expects(self::once())->method('save')->with($user);

        (new ResetPasswordByTokenHandler($this->repository))(
            new ResetPasswordByTokenCommand($token, 'nowe-haslo'),
        );

        self::assertTrue($user->getPassword()->verify('nowe-haslo'));
        self::assertNull($user->getResetToken());
    }

    public function testResetPasswordByTokenThrowsForUnknownToken(): void
    {
        $this->repository->method('findByResetToken')->willReturn(null);
        $this->repository->expects(self::never())->method('save');

        $this->expectException(InvalidResetTokenException::class);

        (new ResetPasswordByTokenHandler($this->repository))(
            new ResetPasswordByTokenCommand('nieznany-token', 'nowe-haslo'),
        );
    }

    public function testResetPasswordByTokenRejectsTooShortPassword(): void
    {
        $user = UserFactory::active();
        $token = $user->requestPasswordReset();
        $this->repository->method('findByResetToken')->willReturn($user);
        $this->repository->expects(self::never())->method('save');

        $this->expectException(\InvalidArgumentException::class);

        (new ResetPasswordByTokenHandler($this->repository))(
            new ResetPasswordByTokenCommand($token, 'krotkie'),
        );
    }
}
