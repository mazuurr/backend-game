<?php

declare(strict_types=1);

namespace App\Campaign\Application\Query\GetCampaign;

use App\Campaign\Application\DTO\CampaignDTO;
use App\Campaign\Domain\Exception\CampaignNotFoundException;
use App\Campaign\Domain\Repository\CampaignRepositoryInterface;
use App\Campaign\Domain\ValueObject\CampaignId;
use App\Puzzle\Application\DTO\PuzzleDTO;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetCampaignHandler
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly PuzzleRepositoryInterface $puzzleRepository,
    ) {}

    public function __invoke(GetCampaignQuery $query): CampaignDTO
    {
        $uuid = new CampaignId($query->uuid);
        $campaign = $this->campaignRepository->findByUuid($uuid);

        if ($campaign === null) {
            throw new CampaignNotFoundException();
        }

        $puzzles = array_map(
            static fn ($puzzle) => PuzzleDTO::fromEntity($puzzle),
            $this->puzzleRepository->findByCampaignId($uuid),
        );

        return CampaignDTO::fromEntity($campaign, $puzzles);
    }
}
