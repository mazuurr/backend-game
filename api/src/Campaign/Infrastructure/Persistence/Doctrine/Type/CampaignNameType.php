<?php

declare(strict_types=1);

namespace App\Campaign\Infrastructure\Persistence\Doctrine\Type;

use App\Campaign\Domain\ValueObject\CampaignName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class CampaignNameType extends StringType
{
    public const NAME = 'campaign_name';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?CampaignName
    {
        if ($value === null) {
            return null;
        }
        return new CampaignName((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof CampaignName ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
