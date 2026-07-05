<?php

declare(strict_types=1);

namespace App\Campaign\Application\Query\GetCampaigns;

use App\Campaign\Application\DTO\CampaignDTO;
use App\Campaign\Domain\Repository\CampaignRepositoryInterface;
use App\Shared\Application\DTO\PaginatedResult;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetCampaignsHandler
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    public function __invoke(GetCampaignsQuery $query): PaginatedResult
    {
        $offset = ($query->page - 1) * $query->perPage;

        $campaigns = $this->campaignRepository->findPaginated($offset, $query->perPage, $query->search);
        $total = $this->campaignRepository->countFiltered($query->search);

        return new PaginatedResult(
            data: array_map(static fn ($campaign) => CampaignDTO::fromEntity($campaign), $campaigns),
            page: $query->page,
            perPage: $query->perPage,
            total: $total,
        );
    }
}
