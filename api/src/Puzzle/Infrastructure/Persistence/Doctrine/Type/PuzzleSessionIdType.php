<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Persistence\Doctrine\Type;

use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class PuzzleSessionIdType extends StringType
{
    public const NAME = 'puzzle_session_id';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?PuzzleSessionId
    {
        if ($value === null) {
            return null;
        }
        return new PuzzleSessionId((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof PuzzleSessionId ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
