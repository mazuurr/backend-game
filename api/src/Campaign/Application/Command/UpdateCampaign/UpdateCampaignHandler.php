<?php

declare(strict_types=1);

namespace App\Campaign\Application\Command\UpdateCampaign;

use App\Campaign\Domain\Exception\CampaignNameAlreadyExistsException;
use App\Campaign\Domain\Exception\CampaignNotFoundException;
use App\Campaign\Domain\Repository\CampaignRepositoryInterface;
use App\Campaign\Domain\ValueObject\CampaignId;
use App\Campaign\Domain\ValueObject\CampaignName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateCampaignHandler
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    public function __invoke(UpdateCampaignCommand $command): void
    {
        $uuid = new CampaignId($command->uuid);
        $campaign = $this->campaignRepository->findByUuid($uuid);

        if ($campaign === null) {
            throw new CampaignNotFoundException();
        }

        $name = null;
        if ($command->name !== null) {
            $name = new CampaignName($command->name);
            if ($this->campaignRepository->nameExistsExcluding($name, $uuid)) {
                throw new CampaignNameAlreadyExistsException();
            }
        }

        $campaign->update($name, $command->description, $command->clearDescription);
        $this->campaignRepository->save($campaign);
    }
}
