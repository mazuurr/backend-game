<?php

declare(strict_types=1);

namespace App\Campaign\Application\Query\GetCampaign;

final class GetCampaignQuery
{
    public function __construct(
        public readonly string $uuid,
    ) {}
}
