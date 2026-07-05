<?php

declare(strict_types=1);

namespace App\Group\Application\Command\InviteUserToGroup;

use App\Group\Domain\Entity\GroupInvitation;
use App\Group\Domain\Exception\GroupInvitationAlreadyExistsException;
use App\Group\Domain\Exception\GroupNotFoundException;
use App\Group\Domain\Exception\NotGroupOwnerException;
use App\Group\Domain\Exception\UserAlreadyInGroupException;
use App\Group\Domain\Repository\GroupInvitationRepositoryInterface;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupId;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class InviteUserToGroupHandler
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly GroupInvitationRepositoryInterface $invitationRepository,
    ) {}

    public function __invoke(InviteUserToGroupCommand $command): void
    {
        $groupId = new GroupId($command->groupUuid);
        $group = $this->groupRepository->findByUuid($groupId);

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        if (!$group->isOwnedBy(new UserId($command->inviterUuid))) {
            throw new NotGroupOwnerException();
        }

        $inviteeId = new UserId($command->inviteeUuid);
        $invitee = $this->userRepository->findByUuid($inviteeId);

        if ($invitee === null) {
            throw new UserNotFoundException();
        }

        if ($invitee->getGroupId() !== null) {
            throw new UserAlreadyInGroupException();
        }

        $existing = $this->invitationRepository->findPendingByUserAndGroup(
            $inviteeId,
            $groupId,
            GroupInvitation::TYPE_INVITATION,
        );

        if ($existing !== null) {
            throw new GroupInvitationAlreadyExistsException();
        }

        $invitation = GroupInvitation::invite(
            groupUuid: $groupId,
            userUuid: $inviteeId,
            inviterUuid: new UserId($command->inviterUuid),
        );

        $this->invitationRepository->save($invitation);
    }
}
