<?php

declare(strict_types=1);

namespace App\Group\Infrastructure\Persistence\Doctrine\Type;

use App\Group\Domain\ValueObject\GroupId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class GroupIdType extends StringType
{
    public const NAME = 'group_id';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?GroupId
    {
        if ($value === null) {
            return null;
        }
        return new GroupId((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof GroupId ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
