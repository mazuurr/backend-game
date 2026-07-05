<?php

declare(strict_types=1);

namespace App\Campaign\Application\Command\CreateCampaign;

use App\Campaign\Domain\Entity\Campaign;
use App\Campaign\Domain\Exception\CampaignNameAlreadyExistsException;
use App\Campaign\Domain\Repository\CampaignRepositoryInterface;
use App\Campaign\Domain\ValueObject\CampaignId;
use App\Campaign\Domain\ValueObject\CampaignName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateCampaignHandler
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    public function __invoke(CreateCampaignCommand $command): void
    {
        $name = new CampaignName($command->name);

        if ($this->campaignRepository->nameExists($name)) {
            throw new CampaignNameAlreadyExistsException();
        }

        $campaign = Campaign::create(
            uuid: CampaignId::generate(),
            name: $name,
            description: $command->description,
        );

        $this->campaignRepository->save($campaign);
    }
}
