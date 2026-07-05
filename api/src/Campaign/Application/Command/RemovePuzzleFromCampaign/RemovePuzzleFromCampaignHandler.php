<?php

declare(strict_types=1);

namespace App\Campaign\Application\Command\RemovePuzzleFromCampaign;

use App\Campaign\Domain\Exception\CampaignNotFoundException;
use App\Campaign\Domain\Repository\CampaignRepositoryInterface;
use App\Campaign\Domain\ValueObject\CampaignId;
use App\Puzzle\Domain\Exception\PuzzleNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RemovePuzzleFromCampaignHandler
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly PuzzleRepositoryInterface $puzzleRepository,
    ) {}

    public function __invoke(RemovePuzzleFromCampaignCommand $command): void
    {
        $campaignId = new CampaignId($command->campaignUuid);

        if ($this->campaignRepository->findByUuid($campaignId) === null) {
            throw new CampaignNotFoundException();
        }

        $puzzle = $this->puzzleRepository->findByUuid(new PuzzleId($command->puzzleUuid));

        if ($puzzle === null) {
            throw new PuzzleNotFoundException();
        }

        $puzzle->removeCampaign();
        $this->puzzleRepository->save($puzzle);
    }
}
