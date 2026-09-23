<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\ValueObject;

use App\User\Domain\ValueObject\HashedPassword;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HashedPassword::class)]
final class HashedPasswordTest extends TestCase
{
    public function testFromPlainNeverStoresThePlainValue(): void
    {
        $password = HashedPassword::fromPlain('tajne-haslo');

        self::assertNotSame('tajne-haslo', $password->value());
        self::assertStringStartsWith('$argon2id$', $password->value());
    }

    public function testFromPlainRejectsShortPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 8 characters');

        HashedPassword::fromPlain('1234567');
    }

    public function testFromPlainAcceptsExactlyEightCharacters(): void
    {
        self::assertTrue(HashedPassword::fromPlain('12345678')->verify('12345678'));
    }

    public function testVerify(): void
    {
        $password = HashedPassword::fromPlain('tajne-haslo');

        self::assertTrue($password->verify('tajne-haslo'));
        self::assertFalse($password->verify('inne-haslo'));
        self::assertFalse($password->verify(''));
    }

    public function testSamePlainProducesDifferentHashes(): void
    {
        self::assertNotSame(
            HashedPassword::fromPlain('tajne-haslo')->value(),
            HashedPassword::fromPlain('tajne-haslo')->value(),
        );
    }

    public function testFromHashKeepsStoredValueVerbatim(): void
    {
        $hash = HashedPassword::fromPlain('tajne-haslo')->value();

        $restored = HashedPassword::fromHash($hash);

        self::assertSame($hash, $restored->value());
        self::assertSame($hash, (string) $restored);
        self::assertTrue($restored->verify('tajne-haslo'));
    }
}
