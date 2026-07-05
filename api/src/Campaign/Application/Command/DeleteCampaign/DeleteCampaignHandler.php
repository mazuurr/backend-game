<?php

declare(strict_types=1);

namespace App\Campaign\Application\Command\DeleteCampaign;

use App\Campaign\Domain\Exception\CampaignNotFoundException;
use App\Campaign\Domain\Repository\CampaignRepositoryInterface;
use App\Campaign\Domain\ValueObject\CampaignId;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteCampaignHandler
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly PuzzleRepositoryInterface $puzzleRepository,
    ) {}

    public function __invoke(DeleteCampaignCommand $command): void
    {
        $uuid = new CampaignId($command->uuid);
        $campaign = $this->campaignRepository->findByUuid($uuid);

        if ($campaign === null) {
            throw new CampaignNotFoundException();
        }

        foreach ($this->puzzleRepository->findByCampaignId($uuid) as $puzzle) {
            $puzzle->removeCampaign();
            $this->puzzleRepository->save($puzzle);
        }

        $this->campaignRepository->remove($campaign);
    }
}
