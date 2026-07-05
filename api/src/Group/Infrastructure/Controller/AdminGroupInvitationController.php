<?php

declare(strict_types=1);

namespace App\Group\Infrastructure\Controller;

use App\Group\Application\Command\AcceptGroupInvitationByAdmin\AcceptGroupInvitationByAdminCommand;
use App\Group\Application\Command\RejectGroupInvitationByAdmin\RejectGroupInvitationByAdminCommand;
use App\Group\Application\Query\GetGroupInvitations\GetGroupInvitationsQuery;
use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
final class AdminGroupInvitationController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {}

    #[Route('/groups/{groupUuid}/invitations', name: 'admin_group_invitations_list', methods: ['GET'])]
    public function listByGroup(string $groupUuid): JsonResponse
    {
        try {
            $invitations = $this->queryBus->ask(new GetGroupInvitationsQuery($groupUuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $invitations]);
    }

    #[Route('/invitations/{uuid}/accept', name: 'admin_invitations_accept', methods: ['POST'])]
    public function accept(string $uuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new AcceptGroupInvitationByAdminCommand(invitationUuid: $uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(['message' => 'Invitation accepted.']);
    }

    #[Route('/invitations/{uuid}/reject', name: 'admin_invitations_reject', methods: ['POST'])]
    public function reject(string $uuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new RejectGroupInvitationByAdminCommand(invitationUuid: $uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(['message' => 'Invitation rejected.']);
    }
}
