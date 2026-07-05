<?php

declare(strict_types=1);

namespace App\Group\Infrastructure\Persistence\Doctrine\Type;

use App\Group\Domain\ValueObject\GroupInvitationId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class GroupInvitationIdType extends StringType
{
    public const NAME = 'group_invitation_id';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?GroupInvitationId
    {
        if ($value === null) {
            return null;
        }
        return new GroupInvitationId((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof GroupInvitationId ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
