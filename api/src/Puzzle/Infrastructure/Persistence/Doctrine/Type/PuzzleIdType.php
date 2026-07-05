<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Persistence\Doctrine\Type;

use App\Puzzle\Domain\ValueObject\PuzzleId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class PuzzleIdType extends StringType
{
    public const NAME = 'puzzle_id';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?PuzzleId
    {
        if ($value === null) {
            return null;
        }
        return new PuzzleId((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof PuzzleId ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
