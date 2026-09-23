<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\ValueObject;

use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\Username;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Username::class)]
final class UsernameTest extends TestCase
{
    public function testTrimsSurroundingWhitespace(): void
    {
        self::assertSame('kamil', (new Username('  kamil  '))->value());
    }

    #[DataProvider('validUsernames')]
    public function testAcceptsAllowedCharacters(string $value): void
    {
        self::assertSame($value, (new Username($value))->value());
    }

    public static function validUsernames(): array
    {
        return [
            'letters' => ['kamil'],
            'digits' => ['user123'],
            'underscore' => ['jan_kowalski'],
            'hyphen' => ['jan-kowalski'],
            'dot' => ['jan.kowalski'],
            'min length' => ['abc'],
            'max length' => [str_repeat('a', 50)],
        ];
    }

    #[DataProvider('invalidUsernames')]
    public function testRejectsInvalidValue(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Username($value);
    }

    public static function invalidUsernames(): array
    {
        return [
            'too short' => ['ab'],
            'too long' => [str_repeat('a', 51)],
            'empty' => [''],
            'whitespace only' => ['     '],
            'space inside' => ['jan kowalski'],
            'at sign' => ['jan@kowalski'],
            'diacritics' => ['zażółć'],
        ];
    }

    public function testGenerateFromEmailUsesLocalPartWithRandomSuffix(): void
    {
        $username = Username::generateFromEmail(new Email('jan.kowalski@example.com'));

        self::assertMatchesRegularExpression('/^jan\.kowalski_[0-9a-f]{6}$/', $username->value());
    }

    public function testGenerateFromEmailReplacesForbiddenCharacters(): void
    {
        $username = Username::generateFromEmail(new Email('jan+tag@example.com'));

        self::assertMatchesRegularExpression('/^jan_tag_[0-9a-f]{6}$/', $username->value());
    }

    public function testGenerateFromEmailPadsShortLocalPart(): void
    {
        $username = Username::generateFromEmail(new Email('ab@example.com'));

        self::assertMatchesRegularExpression('/^ab_[0-9a-f]{4}_[0-9a-f]{6}$/', $username->value());
    }

    public function testGenerateFromEmailStaysWithinLengthLimit(): void
    {
        $localPart = str_repeat('a', 50);

        $username = Username::generateFromEmail(new Email($localPart . '@example.com'));

        self::assertLessThanOrEqual(50, mb_strlen($username->value()));
    }

    public function testGenerateFromEmailIsUniquePerCall(): void
    {
        $email = new Email('jan@example.com');

        self::assertNotSame(
            Username::generateFromEmail($email)->value(),
            Username::generateFromEmail($email)->value(),
        );
    }

    public function testEquals(): void
    {
        self::assertTrue((new Username('kamil'))->equals(new Username('kamil')));
        self::assertFalse((new Username('kamil'))->equals(new Username('Kamil')));
    }
}
