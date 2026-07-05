<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\User\Domain\ValueObject\HashedPassword;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class HashedPasswordType extends StringType
{
    public const NAME = 'hashed_password';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?HashedPassword
    {
        if ($value === null) {
            return null;
        }
        return HashedPassword::fromHash((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof HashedPassword ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
