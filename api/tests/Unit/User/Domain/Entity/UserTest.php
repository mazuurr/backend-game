<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\User\Domain\Entity\User;
use App\User\Domain\Event\UserActivatedEvent;
use App\User\Domain\Event\UserCreatedEvent;
use App\User\Domain\Event\UserDeactivatedEvent;
use App\User\Domain\Exception\ActivationTokenExpiredException;
use App\User\Domain\Exception\InvalidActivationTokenException;
use App\User\Domain\Exception\InvalidCurrentPasswordException;
use App\User\Domain\Exception\InvalidResetTokenException;
use App\User\Domain\Exception\UserAlreadyActiveException;
use App\User\Domain\Exception\UserAlreadyInactiveException;
use App\User\Domain\ValueObject\ActivationToken;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\HashedPassword;
use App\Shared\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\Username;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(User::class)]
final class UserTest extends TestCase
{
    private const PASSWORD = 'tajne-haslo';

    public function testRegisterFromAppCreatesInactiveUserWithActivationToken(): void
    {
        $user = $this->registered();

        self::assertFalse($user->isActive());
        self::assertNotNull($user->getActivationToken());
        self::assertNotNull($user->getTokenExpiresAt());
        self::assertNull($user->getUpdatedAt());
    }

    public function testRegisterFromAppDerivesUsernameFromEmail(): void
    {
        $user = $this->registered('jan.kowalski@example.com');

        self::assertStringStartsWith('jan.kowalski_', $user->getUsername()->value());
    }

    public function testRegisterFromAppTokenExpiresIn24Hours(): void
    {
        $user = $this->registered();

        $expiresAt = $user->getTokenExpiresAt();
        self::assertNotNull($expiresAt);

        $diffHours = ($expiresAt->getTimestamp() - (new \DateTimeImmutable())->getTimestamp()) / 3600;
        self::assertEqualsWithDelta(24, $diffHours, 0.05);
    }

    public function testRegisterFromAppRecordsUserCreatedEvent(): void
    {
        $user = $this->registered('jan@example.com');

        $events = $user->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(UserCreatedEvent::class, $events[0]);
        self::assertSame('jan@example.com', $events[0]->email->value());
        self::assertSame($user->getActivationToken()?->value(), $events[0]->activationToken->value());
    }

    public function testPullDomainEventsClearsTheQueue(): void
    {
        $user = $this->registered();

        self::assertCount(1, $user->pullDomainEvents());
        self::assertSame([], $user->pullDomainEvents());
    }

    public function testCreateByAdminSkipsActivationFlow(): void
    {
        $user = User::createByAdmin(
            uuid: UserId::generate(),
            username: new Username('kamil'),
            email: new Email('kamil@example.com'),
            password: HashedPassword::fromPlain(self::PASSWORD),
            active: true,
        );

        self::assertTrue($user->isActive());
        self::assertNull($user->getActivationToken());
        self::assertNull($user->getTokenExpiresAt());
        self::assertSame([], $user->pullDomainEvents());
    }

    // --- Aktywacja tokenem ---

    public function testActivateByTokenActivatesAndClearsTheToken(): void
    {
        $user = $this->registered();
        $token = (string) $user->getActivationToken();
        $user->pullDomainEvents();

        $user->activateByToken($token);

        self::assertTrue($user->isActive());
        self::assertNull($user->getActivationToken());
        self::assertNull($user->getTokenExpiresAt());
        self::assertNotNull($user->getUpdatedAt());
    }

    public function testActivateByTokenRecordsUserActivatedEvent(): void
    {
        $user = $this->registered();
        $token = (string) $user->getActivationToken();
        $user->pullDomainEvents();

        $user->activateByToken($token);

        $events = $user->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserActivatedEvent::class, $events[0]);
    }

    public function testActivateByTokenRejectsWrongToken(): void
    {
        $user = $this->registered();

        $this->expectException(InvalidActivationTokenException::class);

        $user->activateByToken('zly-token');
    }

    public function testActivateByTokenRejectsExpiredToken(): void
    {
        $user = $this->registered();
        $token = (string) $user->getActivationToken();
        $this->setPrivate($user, 'tokenExpiresAt', new \DateTimeImmutable('-1 second'));

        $this->expectException(ActivationTokenExpiredException::class);

        $user->activateByToken($token);
    }

    public function testActivateByTokenRejectsAlreadyActiveUser(): void
    {
        $user = $this->activeUser();

        $this->expectException(UserAlreadyActiveException::class);

        $user->activateByToken('dowolny-token');
    }

    // --- Aktywacja/dezaktywacja przez admina ---

    public function testActivateByAdminClearsPendingToken(): void
    {
        $user = $this->registered();

        $user->activateByAdmin();

        self::assertTrue($user->isActive());
        self::assertNull($user->getActivationToken());
        self::assertNull($user->getTokenExpiresAt());
    }

    public function testActivateByAdminRejectsActiveUser(): void
    {
        $this->expectException(UserAlreadyActiveException::class);

        $this->activeUser()->activateByAdmin();
    }

    public function testDeactivateRecordsEvent(): void
    {
        $user = $this->activeUser();

        $user->deactivate();

        self::assertFalse($user->isActive());
        $events = $user->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserDeactivatedEvent::class, $events[0]);
    }

    public function testDeactivateRejectsInactiveUser(): void
    {
        $this->expectException(UserAlreadyInactiveException::class);

        $this->registered()->deactivate();
    }

    // --- Edycja ---

    public function testUpdateByAdminChangesEveryProvidedField(): void
    {
        $user = $this->activeUser();

        $user->updateByAdmin(
            username: new Username('nowy_login'),
            email: new Email('nowy@example.com'),
            password: HashedPassword::fromPlain('nowe-haslo'),
            active: false,
        );

        self::assertSame('nowy_login', $user->getUsername()->value());
        self::assertSame('nowy@example.com', $user->getEmail()->value());
        self::assertTrue($user->getPassword()->verify('nowe-haslo'));
        self::assertFalse($user->isActive());
    }

    public function testUpdateByAdminLeavesNullFieldsUntouched(): void
    {
        $user = $this->activeUser();

        $user->updateByAdmin(null, null, null, null);

        self::assertSame('kamil', $user->getUsername()->value());
        self::assertSame('kamil@example.com', $user->getEmail()->value());
        self::assertTrue($user->getPassword()->verify(self::PASSWORD));
        self::assertTrue($user->isActive());
        self::assertNotNull($user->getUpdatedAt());
    }

    public function testUpdateBySelfCannotTouchActiveFlag(): void
    {
        $user = $this->activeUser();

        $user->updateBySelf(
            username: new Username('nowy_login'),
            email: new Email('nowy@example.com'),
            password: HashedPassword::fromPlain('nowe-haslo'),
        );

        self::assertSame('nowy_login', $user->getUsername()->value());
        self::assertTrue($user->isActive());
    }

    // --- Hasło ---

    public function testChangePasswordRequiresCurrentPassword(): void
    {
        $user = $this->activeUser();

        $user->changePassword(self::PASSWORD, HashedPassword::fromPlain('nowe-haslo'));

        self::assertTrue($user->getPassword()->verify('nowe-haslo'));
    }

    public function testChangePasswordRejectsWrongCurrentPassword(): void
    {
        $user = $this->activeUser();

        $this->expectException(InvalidCurrentPasswordException::class);

        $user->changePassword('zle-haslo', HashedPassword::fromPlain('nowe-haslo'));
    }

    public function testRequestPasswordResetIssuesTokenValidForOneHour(): void
    {
        $user = $this->activeUser();

        $token = $user->requestPasswordReset();

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        self::assertSame($token, $user->getResetToken());

        $expiresAt = $user->getResetTokenExpiresAt();
        self::assertNotNull($expiresAt);
        $diffMinutes = ($expiresAt->getTimestamp() - (new \DateTimeImmutable())->getTimestamp()) / 60;
        self::assertEqualsWithDelta(60, $diffMinutes, 1);
    }

    public function testRequestPasswordResetOverwritesPreviousToken(): void
    {
        $user = $this->activeUser();

        $first = $user->requestPasswordReset();
        $second = $user->requestPasswordReset();

        self::assertNotSame($first, $second);
        self::assertSame($second, $user->getResetToken());
    }

    public function testResetPasswordByTokenClearsTheToken(): void
    {
        $user = $this->activeUser();
        $token = $user->requestPasswordReset();

        $user->resetPasswordByToken($token, HashedPassword::fromPlain('nowe-haslo'));

        self::assertTrue($user->getPassword()->verify('nowe-haslo'));
        self::assertNull($user->getResetToken());
        self::assertNull($user->getResetTokenExpiresAt());
    }

    public function testResetPasswordByTokenRejectsUnknownToken(): void
    {
        $user = $this->activeUser();
        $user->requestPasswordReset();

        $this->expectException(InvalidResetTokenException::class);

        $user->resetPasswordByToken('zly-token', HashedPassword::fromPlain('nowe-haslo'));
    }

    public function testResetPasswordByTokenRejectsWhenNoResetWasRequested(): void
    {
        $this->expectException(InvalidResetTokenException::class);

        $this->activeUser()->resetPasswordByToken('jakis-token', HashedPassword::fromPlain('nowe-haslo'));
    }

    public function testResetPasswordByTokenRejectsExpiredToken(): void
    {
        $user = $this->activeUser();
        $token = $user->requestPasswordReset();
        $this->setPrivate($user, 'resetTokenExpiresAt', new \DateTimeImmutable('-1 second'));

        $this->expectException(InvalidResetTokenException::class);

        $user->resetPasswordByToken($token, HashedPassword::fromPlain('nowe-haslo'));
    }

    public function testResetPasswordFailureLeavesOldPasswordInPlace(): void
    {
        $user = $this->activeUser();
        $user->requestPasswordReset();

        try {
            $user->resetPasswordByToken('zly-token', HashedPassword::fromPlain('nowe-haslo'));
        } catch (InvalidResetTokenException) {
        }

        self::assertTrue($user->getPassword()->verify(self::PASSWORD));
    }

    // --- Grupa ---

    // --- Pomocnicze ---

    private function registered(string $email = 'kamil@example.com'): User
    {
        return User::registerFromApp(
            uuid: UserId::generate(),
            email: new Email($email),
            password: HashedPassword::fromPlain(self::PASSWORD),
        );
    }

    private function activeUser(): User
    {
        return User::createByAdmin(
            uuid: UserId::generate(),
            username: new Username('kamil'),
            email: new Email('kamil@example.com'),
            password: HashedPassword::fromPlain(self::PASSWORD),
            active: true,
        );
    }

    /**
     * Both expiry timestamps are set from `new DateTimeImmutable()` inside the entity,
     * so travelling into the past is the only way to exercise the expiry branches.
     */
    private function setPrivate(User $user, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty(User::class, $property);
        $reflection->setValue($user, $value);
    }

    public function testActivationTokenValueObjectIsAcceptedAsRawString(): void
    {
        $user = $this->registered();
        $token = $user->getActivationToken();

        self::assertInstanceOf(ActivationToken::class, $token);

        $user->activateByToken($token->value());

        self::assertTrue($user->isActive());
    }
}
