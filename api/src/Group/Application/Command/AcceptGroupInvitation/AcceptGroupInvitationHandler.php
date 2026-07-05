<?php

declare(strict_types=1);

namespace App\Group\Application\Command\AcceptGroupInvitation;

use App\Group\Domain\Entity\GroupInvitation;
use App\Group\Domain\Exception\GroupInvitationNotFoundException;
use App\Group\Domain\Exception\GroupInvitationNotPendingException;
use App\Group\Domain\Exception\NotGroupOwnerException;
use App\Group\Domain\Repository\GroupInvitationRepositoryInterface;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupInvitationId;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class AcceptGroupInvitationHandler
{
    public function __construct(
        private readonly GroupInvitationRepositoryInterface $invitationRepository,
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(AcceptGroupInvitationCommand $command): void
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

        $invitation->accept();

        $user = $this->userRepository->findByUuid($invitation->getUserUuid());

        if ($user !== null) {
            $user->assignToGroup($invitation->getGroupUuid());
            $this->userRepository->save($user);
        }

        $this->invitationRepository->save($invitation);
    }

    private function authorizeActor(GroupInvitation $invitation, UserId $actorId): void
    {
        if ($invitation->isInvitation()) {
            // User accepts their own invitation from the group
            if (!$invitation->getUserUuid()->equals($actorId)) {
                throw new NotGroupOwnerException();
            }
            return;
        }

        // Group owner (or admin via separate endpoint) accepts a join request
        $group = $this->groupRepository->findByUuid($invitation->getGroupUuid());

        if ($group === null || !$group->isOwnedBy($actorId)) {
            throw new NotGroupOwnerException();
        }
    }
}
