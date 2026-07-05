<?php

declare(strict_types=1);

namespace App\Group\Infrastructure\Controller;

use App\Group\Application\Command\AssignUserToGroup\AssignUserToGroupCommand;
use App\Group\Application\Command\CreateGroup\CreateGroupCommand;
use App\Group\Application\Command\DeleteGroup\DeleteGroupCommand;
use App\Group\Application\Command\RemoveUserFromGroup\RemoveUserFromGroupCommand;
use App\Group\Application\Command\UpdateGroup\UpdateGroupCommand;
use App\Group\Application\Query\GetGroup\GetGroupQuery;
use App\Group\Application\Query\GetGroups\GetGroupsQuery;
use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/groups')]
final class AdminGroupController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {}

    #[Route('', name: 'admin_groups_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(100, (int) $request->query->get('per_page', 20)));
        $search = $request->query->get('search') ?: null;

        $result = $this->queryBus->ask(new GetGroupsQuery(
            page: $page,
            perPage: $perPage,
            search: $search,
        ));

        return new JsonResponse($result);
    }

    #[Route('/{uuid}', name: 'admin_groups_show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $group = $this->queryBus->ask(new GetGroupQuery($uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $group]);
    }

    #[Route('', name: 'admin_groups_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->commandBus->dispatch(new CreateGroupCommand(
                name: $data['name'] ?? throw new \InvalidArgumentException('Name is required.'),
                description: $data['description'] ?? null,
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(['message' => 'Group created.'], Response::HTTP_CREATED);
    }

    #[Route('/{uuid}', name: 'admin_groups_update', methods: ['PUT', 'PATCH'])]
    public function update(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->commandBus->dispatch(new UpdateGroupCommand(
                uuid: $uuid,
                name: $data['name'] ?? null,
                description: $data['description'] ?? null,
                clearDescription: isset($data['description']) && $data['description'] === null,
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Group updated.']);
    }

    #[Route('/{uuid}', name: 'admin_groups_delete', methods: ['DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new DeleteGroupCommand($uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{groupUuid}/users/{userUuid}', name: 'admin_groups_assign_user', methods: ['POST'])]
    public function assignUser(string $groupUuid, string $userUuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new AssignUserToGroupCommand($groupUuid, $userUuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'User assigned to group.']);
    }

    #[Route('/{groupUuid}/users/{userUuid}', name: 'admin_groups_remove_user', methods: ['DELETE'])]
    public function removeUser(string $groupUuid, string $userUuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new RemoveUserFromGroupCommand($groupUuid, $userUuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
