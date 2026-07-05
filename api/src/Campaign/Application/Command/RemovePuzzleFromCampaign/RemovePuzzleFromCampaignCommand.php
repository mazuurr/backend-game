<?php

declare(strict_types=1);

namespace App\Campaign\Application\Command\RemovePuzzleFromCampaign;

final class RemovePuzzleFromCampaignCommand
{
    public function __construct(
        public readonly string $campaignUuid,
        public readonly string $puzzleUuid,
    ) {}
}
