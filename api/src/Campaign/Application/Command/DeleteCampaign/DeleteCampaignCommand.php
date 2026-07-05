<?php

declare(strict_types=1);

namespace App\Campaign\Application\Command\DeleteCampaign;

final class DeleteCampaignCommand
{
    public function __construct(
        public readonly string $uuid,
    ) {}
}
