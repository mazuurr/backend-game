<?php

declare(strict_types=1);

namespace App\Campaign\Application\Query\GetCampaigns;

final class GetCampaignsQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly ?string $search = null,
    ) {}
}
