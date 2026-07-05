<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\User\Domain\ValueObject\Username;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class UsernameType extends StringType
{
    public const NAME = 'username';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Username
    {
        if ($value === null) {
            return null;
        }
        return new Username((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof Username ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
