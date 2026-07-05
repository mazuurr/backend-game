<?php

declare(strict_types=1);

namespace App\Group\Infrastructure\Controller;

use App\Group\Application\Command\AcceptGroupInvitation\AcceptGroupInvitationCommand;
use App\Group\Application\Command\RejectGroupInvitation\RejectGroupInvitationCommand;
use App\Group\Application\Query\GetMyGroupInvitations\GetMyGroupInvitationsQuery;
use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/invitations')]
final class UserGroupInvitationController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {}

    #[Route('', name: 'user_invitations_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $invitations = $this->queryBus->ask(new GetMyGroupInvitationsQuery(
            userUuid: $this->getSecurityUser()->getUuid(),
        ));

        return new JsonResponse(['data' => $invitations]);
    }

    #[Route('/{uuid}/accept', name: 'user_invitations_accept', methods: ['POST'])]
    public function accept(string $uuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new AcceptGroupInvitationCommand(
                invitationUuid: $uuid,
                actorUuid: $this->getSecurityUser()->getUuid(),
            ));
        } catch (\DomainException $e) {
            $status = str_contains($e->getMessage(), 'owner') ? Response::HTTP_FORBIDDEN : Response::HTTP_CONFLICT;

            return new JsonResponse(['error' => $e->getMessage()], $status);
        }

        return new JsonResponse(['message' => 'Invitation accepted.']);
    }

    #[Route('/{uuid}/reject', name: 'user_invitations_reject', methods: ['POST'])]
    public function reject(string $uuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new RejectGroupInvitationCommand(
                invitationUuid: $uuid,
                actorUuid: $this->getSecurityUser()->getUuid(),
            ));
        } catch (\DomainException $e) {
            $status = str_contains($e->getMessage(), 'owner') ? Response::HTTP_FORBIDDEN : Response::HTTP_CONFLICT;

            return new JsonResponse(['error' => $e->getMessage()], $status);
        }

        return new JsonResponse(['message' => 'Invitation rejected.']);
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
