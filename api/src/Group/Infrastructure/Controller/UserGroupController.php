<?php

declare(strict_types=1);

namespace App\Group\Infrastructure\Controller;

use App\Group\Application\Command\CreateGroupByUser\CreateGroupByUserCommand;
use App\Group\Application\Command\DeleteGroupByUser\DeleteGroupByUserCommand;
use App\Group\Application\Command\InviteUserToGroup\InviteUserToGroupCommand;
use App\Group\Application\Command\JoinGroup\JoinGroupCommand;
use App\Group\Application\Command\LeaveGroup\LeaveGroupCommand;
use App\Group\Application\Command\TransferOwnership\TransferOwnershipCommand;
use App\Group\Application\Query\GetGroup\GetGroupQuery;
use App\Group\Application\Query\GetGroups\GetGroupsQuery;
use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/groups')]
final class UserGroupController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {}

    #[Route('', name: 'user_groups_list', methods: ['GET'])]
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

    #[Route('/{uuid}', name: 'user_groups_show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $group = $this->queryBus->ask(new GetGroupQuery($uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $group]);
    }

    #[Route('', name: 'user_groups_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->commandBus->dispatch(new CreateGroupByUserCommand(
                name: $data['name'] ?? throw new \InvalidArgumentException('Name is required.'),
                description: $data['description'] ?? null,
                ownerUuid: $this->getSecurityUser()->getUuid(),
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(['message' => 'Group created.'], Response::HTTP_CREATED);
    }

    #[Route('/{uuid}/join', name: 'user_groups_join', methods: ['POST'])]
    public function join(string $uuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new JoinGroupCommand(
                groupUuid: $uuid,
                userUuid: $this->getSecurityUser()->getUuid(),
            ));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(['message' => 'Joined group.']);
    }

    #[Route('/leave', name: 'user_groups_leave', methods: ['POST'])]
    public function leave(): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new LeaveGroupCommand(
                userUuid: $this->getSecurityUser()->getUuid(),
            ));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['message' => 'Left group.']);
    }

    #[Route('/{uuid}', name: 'user_groups_delete', methods: ['DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new DeleteGroupByUserCommand(
                groupUuid: $uuid,
                requesterUuid: $this->getSecurityUser()->getUuid(),
            ));
        } catch (\DomainException $e) {
            $status = str_contains($e->getMessage(), 'owner') ? Response::HTTP_FORBIDDEN : Response::HTTP_NOT_FOUND;

            return new JsonResponse(['error' => $e->getMessage()], $status);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{uuid}/transfer-ownership', name: 'user_groups_transfer_ownership', methods: ['POST'])]
    public function transferOwnership(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->commandBus->dispatch(new TransferOwnershipCommand(
                groupUuid: $uuid,
                requesterUuid: $this->getSecurityUser()->getUuid(),
                newOwnerUuid: $data['new_owner_uuid'] ?? throw new \InvalidArgumentException('new_owner_uuid is required.'),
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            $status = str_contains($e->getMessage(), 'owner') ? Response::HTTP_FORBIDDEN : Response::HTTP_UNPROCESSABLE_ENTITY;

            return new JsonResponse(['error' => $e->getMessage()], $status);
        }

        return new JsonResponse(['message' => 'Ownership transferred.']);
    }

    #[Route('/{uuid}/invite/{userUuid}', name: 'user_groups_invite', methods: ['POST'])]
    public function invite(string $uuid, string $userUuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new InviteUserToGroupCommand(
                groupUuid: $uuid,
                inviterUuid: $this->getSecurityUser()->getUuid(),
                inviteeUuid: $userUuid,
            ));
        } catch (\DomainException $e) {
            $status = str_contains($e->getMessage(), 'owner') ? Response::HTTP_FORBIDDEN : Response::HTTP_CONFLICT;

            return new JsonResponse(['error' => $e->getMessage()], $status);
        }

        return new JsonResponse(['message' => 'User invited to group.']);
    }

    private function getSecurityUser(): SecurityUser
    {
        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new \RuntimeException('User not authenticated.');
        }

        return $user;
    }
}
