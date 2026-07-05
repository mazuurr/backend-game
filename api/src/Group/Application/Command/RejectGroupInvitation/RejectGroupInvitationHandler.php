<?php

declare(strict_types=1);

namespace App\Group\Application\Command\RejectGroupInvitation;

use App\Group\Domain\Entity\GroupInvitation;
use App\Group\Domain\Exception\GroupInvitationNotFoundException;
use App\Group\Domain\Exception\GroupInvitationNotPendingException;
use App\Group\Domain\Exception\NotGroupOwnerException;
use App\Group\Domain\Repository\GroupInvitationRepositoryInterface;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupInvitationId;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RejectGroupInvitationHandler
{
    public function __construct(
        private readonly GroupInvitationRepositoryInterface $invitationRepository,
        private readonly GroupRepositoryInterface $groupRepository,
    ) {}

    public function __invoke(RejectGroupInvitationCommand $command): void
    {
        $invitation = $this->invitationRepository->findByUuid(new GroupInvitationId($command->invitationUuid));

        if ($invitation === null) {
            throw new GroupInvitationNotFoundException();
        }

        if (!$invitation->isPending()) {
            throw new GroupInvitationNotPendingException();
        }

        $actorId = new UserId($command->actorUuid);

        $this->authorizeActor($invitation, $actorId);

        $invitation->reject();
        $this->invitationRepository->save($invitation);
    }

    private function authorizeActor(GroupInvitation $invitation, UserId $actorId): void
    {
        if ($invitation->isInvitation()) {
            // User rejects/cancels invitation they received
            if (!$invitation->getUserUuid()->equals($actorId)) {
                throw new NotGroupOwnerException();
            }
            return;
        }

        // Request: the requester can cancel their own request, or the group owner can reject it
        if ($invitation->getUserUuid()->equals($actorId)) {
            return;
        }

        $group = $this->groupRepository->findByUuid($invitation->getGroupUuid());

        if ($group === null || !$group->isOwnedBy($actorId)) {
            throw new NotGroupOwnerException();
        }
    }
}
