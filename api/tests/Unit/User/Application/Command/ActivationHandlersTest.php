<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Support\UserFactory;
use App\User\Application\Command\ActivateUser\ActivateUserCommand;
use App\User\Application\Command\ActivateUser\ActivateUserHandler;
use App\User\Application\Command\ActivateUserByToken\ActivateUserByTokenCommand;
use App\User\Application\Command\ActivateUserByToken\ActivateUserByTokenHandler;
use App\User\Application\Command\DeactivateUser\DeactivateUserCommand;
use App\User\Application\Command\DeactivateUser\DeactivateUserHandler;
use App\User\Application\Command\DeleteUser\DeleteUserCommand;
use App\User\Application\Command\DeleteUser\DeleteUserHandler;
use App\User\Domain\Exception\InvalidActivationTokenException;
use App\User\Domain\Exception\UserAlreadyActiveException;
use App\User\Domain\Exception\UserAlreadyInactiveException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ActivateUserHandler::class)]
#[CoversClass(ActivateUserByTokenHandler::class)]
#[CoversClass(DeactivateUserHandler::class)]
#[CoversClass(DeleteUserHandler::class)]
final class ActivationHandlersTest extends TestCase
{
    private UserRepositoryInterface&MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
    }

    // --- ActivateUser (admin) ---

    public function testActivateUserActivatesPendingAccount(): void
    {
        $user = UserFactory::pendingActivation();
        $this->repository->method('findByUuid')->willReturn($user);
        $this->repository->expects(self::once())->method('save')->with($user);

        (new ActivateUserHandler($this->repository))(new ActivateUserCommand(UserId::generate()->value()));

        self::assertTrue($user->isActive());
    }

    public function testActivateUserThrowsWhenUserMissing(): void
    {
        $this->repository->method('findByUuid')->willReturn(null);
        $this->repository->expects(self::never())->method('save');

        $this->expectException(UserNotFoundException::class);

        (new ActivateUserHandler($this->repository))(new ActivateUserCommand(UserId::generate()->value()));
    }

    public function testActivateUserThrowsWhenAlreadyActive(): void
    {
        $this->repository->method('findByUuid')->willReturn(UserFactory::active());
        $this->repository->expects(self::never())->method('save');

        $this->expectException(UserAlreadyActiveException::class);

        (new ActivateUserHandler($this->repository))(new ActivateUserCommand(UserId::generate()->value()));
    }

    // --- ActivateUserByToken ---

    public function testActivateByTokenLooksUpUserByTheTokenItself(): void
    {
        $user = UserFactory::pendingActivation();
        $token = (string) $user->getActivationToken();

        $this->repository->expects(self::once())
            ->method('findByActivationToken')
            ->with($token)
            ->willReturn($user);
        $this->repository->expects(self::once())->method('save')->with($user);

        (new ActivateUserByTokenHandler($this->repository))(new ActivateUserByTokenCommand($token));

        self::assertTrue($user->isActive());
        self::assertNull($user->getActivationToken());
    }

    public function testActivateByTokenThrowsForUnknownToken(): void
    {
        $this->repository->method('findByActivationToken')->willReturn(null);
        $this->repository->expects(self::never())->method('save');

        $this->expectException(UserNotFoundException::class);

        (new ActivateUserByTokenHandler($this->repository))(new ActivateUserByTokenCommand('nieznany-token'));
    }

    public function testActivateByTokenRejectsMismatchedToken(): void
    {
        // Repository lookup succeeded, but the entity guard still rejects a different token.
        $this->repository->method('findByActivationToken')->willReturn(UserFactory::pendingActivation());
        $this->repository->expects(self::never())->method('save');

        $this->expectException(InvalidActivationTokenException::class);

        (new ActivateUserByTokenHandler($this->repository))(new ActivateUserByTokenCommand('inny-token'));
    }

    // --- DeactivateUser ---

    public function testDeactivateUser(): void
    {
        $user = UserFactory::active();
        $this->repository->method('findByUuid')->willReturn($user);
        $this->repository->expects(self::once())->method('save')->with($user);

        (new DeactivateUserHandler($this->repository))(new DeactivateUserCommand(UserId::generate()->value()));

        self::assertFalse($user->isActive());
    }

    public function testDeactivateUserThrowsWhenAlreadyInactive(): void
    {
        $this->repository->method('findByUuid')->willReturn(UserFactory::pendingActivation());
        $this->repository->expects(self::never())->method('save');

        $this->expectException(UserAlreadyInactiveException::class);

        (new DeactivateUserHandler($this->repository))(new DeactivateUserCommand(UserId::generate()->value()));
    }

    public function testDeactivateUserThrowsWhenUserMissing(): void
    {
        $this->repository->method('findByUuid')->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        (new DeactivateUserHandler($this->repository))(new DeactivateUserCommand(UserId::generate()->value()));
    }

    // --- DeleteUser ---

    public function testDeleteUserRemovesTheEntity(): void
    {
        $user = UserFactory::active();
        $this->repository->method('findByUuid')->willReturn($user);
        $this->repository->expects(self::once())->method('remove')->with($user);

        (new DeleteUserHandler($this->repository))(new DeleteUserCommand(UserId::generate()->value()));
    }

    public function testDeleteUserThrowsWhenUserMissing(): void
    {
        $this->repository->method('findByUuid')->willReturn(null);
        $this->repository->expects(self::never())->method('remove');

        $this->expectException(UserNotFoundException::class);

        (new DeleteUserHandler($this->repository))(new DeleteUserCommand(UserId::generate()->value()));
    }

    public function testInvalidUuidIsRejectedBeforeAnyRepositoryCall(): void
    {
        $this->repository->expects(self::never())->method('findByUuid');

        $this->expectException(\InvalidArgumentException::class);

        (new DeleteUserHandler($this->repository))(new DeleteUserCommand('nie-uuid'));
    }
}
