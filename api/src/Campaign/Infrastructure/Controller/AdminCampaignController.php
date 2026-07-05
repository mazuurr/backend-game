<?php

declare(strict_types=1);

namespace App\Campaign\Infrastructure\Controller;

use App\Campaign\Application\Command\AssignPuzzleToCampaign\AssignPuzzleToCampaignCommand;
use App\Campaign\Application\Command\CreateCampaign\CreateCampaignCommand;
use App\Campaign\Application\Command\DeleteCampaign\DeleteCampaignCommand;
use App\Campaign\Application\Command\RemovePuzzleFromCampaign\RemovePuzzleFromCampaignCommand;
use App\Campaign\Application\Command\UpdateCampaign\UpdateCampaignCommand;
use App\Campaign\Application\Query\GetCampaign\GetCampaignQuery;
use App\Campaign\Application\Query\GetCampaigns\GetCampaignsQuery;
use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/campaigns')]
final class AdminCampaignController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {}

    #[Route('', name: 'admin_campaigns_list', methods: ['GET'])]
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

    #[Route('/{uuid}', name: 'admin_campaigns_show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $campaign = $this->queryBus->ask(new GetCampaignQuery($uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $campaign]);
    }

    #[Route('', name: 'admin_campaigns_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->commandBus->dispatch(new CreateCampaignCommand(
                name: $data['name'] ?? throw new \InvalidArgumentException('Name is required.'),
                description: $data['description'] ?? null,
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(['message' => 'Campaign created.'], Response::HTTP_CREATED);
    }

    #[Route('/{uuid}', name: 'admin_campaigns_update', methods: ['PUT', 'PATCH'])]
    public function update(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->commandBus->dispatch(new UpdateCampaignCommand(
                uuid: $uuid,
                name: $data['name'] ?? null,
                description: $data['description'] ?? null,
                clearDescription: isset($data['description']) && $data['description'] === null,
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            $status = str_contains($e->getMessage(), 'not found') ? Response::HTTP_NOT_FOUND : Response::HTTP_CONFLICT;

            return new JsonResponse(['error' => $e->getMessage()], $status);
        }

        return new JsonResponse(['message' => 'Campaign updated.']);
    }

    #[Route('/{uuid}', name: 'admin_campaigns_delete', methods: ['DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new DeleteCampaignCommand(uuid: $uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{campaignUuid}/puzzles/{puzzleUuid}', name: 'admin_campaigns_assign_puzzle', methods: ['POST'])]
    public function assignPuzzle(string $campaignUuid, string $puzzleUuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new AssignPuzzleToCampaignCommand(
                campaignUuid: $campaignUuid,
                puzzleUuid: $puzzleUuid,
            ));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Puzzle assigned to campaign.']);
    }

    #[Route('/{campaignUuid}/puzzles/{puzzleUuid}', name: 'admin_campaigns_remove_puzzle', methods: ['DELETE'])]
    public function removePuzzle(string $campaignUuid, string $puzzleUuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new RemovePuzzleFromCampaignCommand(
                campaignUuid: $campaignUuid,
                puzzleUuid: $puzzleUuid,
            ));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
