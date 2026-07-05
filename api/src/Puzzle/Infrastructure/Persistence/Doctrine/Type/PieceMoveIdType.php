<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Persistence\Doctrine\Type;

use App\Puzzle\Domain\ValueObject\PieceMoveId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class PieceMoveIdType extends StringType
{
    public const NAME = 'piece_move_id';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?PieceMoveId
    {
        if ($value === null) {
            return null;
        }
        return new PieceMoveId((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof PieceMoveId ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
