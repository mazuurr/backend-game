<?php

declare(strict_types=1);

namespace App\Campaign\Infrastructure\Persistence\Doctrine\Type;

use App\Campaign\Domain\ValueObject\CampaignId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class CampaignIdType extends StringType
{
    public const NAME = 'campaign_id';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?CampaignId
    {
        if ($value === null) {
            return null;
        }
        return new CampaignId((string) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof CampaignId ? $value->value() : (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
