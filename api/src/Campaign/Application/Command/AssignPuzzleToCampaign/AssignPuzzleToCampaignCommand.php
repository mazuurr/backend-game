<?php

declare(strict_types=1);

namespace App\Campaign\Application\Command\AssignPuzzleToCampaign;

final class AssignPuzzleToCampaignCommand
{
    public function __construct(
        public readonly string $campaignUuid,
        public readonly string $puzzleUuid,
    ) {}
}
