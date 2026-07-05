<?php

declare(strict_types=1);

namespace App\Campaign\Infrastructure\Controller;

use App\Campaign\Application\Query\GetCampaign\GetCampaignQuery;
use App\Campaign\Application\Query\GetCampaigns\GetCampaignsQuery;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/campaigns')]
final class UserCampaignController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    #[Route('', name: 'user_campaigns_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(100, (int) $request->query->get('per_page', 20)));
        $search = $request->query->get('search') ?: null;

        $result = $this->queryBus->ask(new GetCampaignsQuery(
            page: $page,
            perPage: $perPage,
            search: $search,
        ));

        return new JsonResponse($result);
    }

    #[Route('/{uuid}', name: 'user_campaigns_show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $campaign = $this->queryBus->ask(new GetCampaignQuery($uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $campaign]);
    }
}
