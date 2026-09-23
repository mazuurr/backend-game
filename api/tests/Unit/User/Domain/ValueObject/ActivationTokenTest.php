<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\ValueObject;

use App\User\Domain\ValueObject\ActivationToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ActivationToken::class)]
final class ActivationTokenTest extends TestCase
{
    public function testRejectsEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ActivationToken('');
    }

    public function testGenerateProduces64HexCharacters(): void
    {
        $token = ActivationToken::generate();

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token->value());
    }

    public function testGenerateIsUniquePerCall(): void
    {
        self::assertNotSame(
            ActivationToken::generate()->value(),
            ActivationToken::generate()->value(),
        );
    }

    public function testEqualsComparesAgainstRawString(): void
    {
        $token = new ActivationToken('abc123');

        self::assertTrue($token->equals('abc123'));
        self::assertFalse($token->equals('abc124'));
        self::assertFalse($token->equals('ABC123'));
        self::assertFalse($token->equals('abc123 '));
    }

    public function testToString(): void
    {
        self::assertSame('abc123', (string) new ActivationToken('abc123'));
    }
}
