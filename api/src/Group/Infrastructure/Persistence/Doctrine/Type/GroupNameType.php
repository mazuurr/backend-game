<?php

declare(strict_types=1);

namespace App\Group\Infrastructure\Persistence\Doctrine\Type;

use App\Group\Domain\ValueObject\GroupName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class GroupNameType extends StringType
{
    public const NAME = 'group_name';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?GroupName
    {
        if ($value === null) {
            return null;
        }
        return new GroupName((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof GroupName ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
