<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\HashedPassword;
use App\Shared\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\Username;

final class UserFactory
{
    public const PASSWORD = 'tajne-haslo';

    /** Argon2id hashing costs ~0.1s a call; one hash per process is plenty for the suite. */
    private static ?string $passwordHash = null;

    public static function password(): HashedPassword
    {
        self::$passwordHash ??= HashedPassword::fromPlain(self::PASSWORD)->value();

        return HashedPassword::fromHash(self::$passwordHash);
    }

    public static function active(
        ?UserId $uuid = null,
        string $username = 'kamil',
        string $email = 'kamil@example.com',
    ): User {
        $user = User::createByAdmin(
            uuid: $uuid ?? UserId::generate(),
            username: new Username($username),
            email: new Email($email),
            password: self::password(),
            active: true,
        );

        return $user;
    }

    public static function pendingActivation(string $email = 'kamil@example.com'): User
    {
        return User::registerFromApp(
            uuid: UserId::generate(),
            email: new Email($email),
            password: self::password(),
        );
    }
}
