<?php

declare(strict_types=1);

namespace App\Campaign\Application\Command\CreateCampaign;

final class CreateCampaignCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
    ) {}
}
