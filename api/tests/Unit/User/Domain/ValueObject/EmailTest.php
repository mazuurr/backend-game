<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\ValueObject;

use App\User\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Email::class)]
final class EmailTest extends TestCase
{
    public function testNormalizesToLowercase(): void
    {
        $email = new Email('Jan.Kowalski@Example.COM');

        self::assertSame('jan.kowalski@example.com', $email->value());
        self::assertSame('jan.kowalski@example.com', (string) $email);
    }

    #[DataProvider('invalidEmails')]
    public function testRejectsInvalidAddress(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Email($value);
    }

    public static function invalidEmails(): array
    {
        return [
            'empty' => [''],
            'no at sign' => ['jan.kowalski'],
            'no domain' => ['jan@'],
            'no local part' => ['@example.com'],
            'space inside' => ['jan kowalski@example.com'],
        ];
    }

    public function testEqualsIgnoresOriginalCase(): void
    {
        self::assertTrue((new Email('a@example.com'))->equals(new Email('A@EXAMPLE.COM')));
        self::assertFalse((new Email('a@example.com'))->equals(new Email('b@example.com')));
    }
}
