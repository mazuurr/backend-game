<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\User\Domain\ValueObject\ActivationToken;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class ActivationTokenType extends StringType
{
    public const NAME = 'activation_token';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?ActivationToken
    {
        if ($value === null) {
            return null;
        }
        return new ActivationToken((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof ActivationToken ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
