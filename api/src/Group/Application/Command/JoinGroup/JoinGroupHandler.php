<?php

declare(strict_types=1);

namespace App\Group\Application\Command\JoinGroup;

use App\Group\Domain\Entity\GroupInvitation;
use App\Group\Domain\Exception\GroupInvitationAlreadyExistsException;
use App\Group\Domain\Exception\GroupNotFoundException;
use App\Group\Domain\Exception\UserAlreadyInGroupException;
use App\Group\Domain\Repository\GroupInvitationRepositoryInterface;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupId;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class JoinGroupHandler
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly GroupInvitationRepositoryInterface $invitationRepository,
    ) {}

    public function __invoke(JoinGroupCommand $command): void
    {
        $groupId = new GroupId($command->groupUuid);

        if ($this->groupRepository->findByUuid($groupId) === null) {
            throw new GroupNotFoundException();
        }

        $userId = new UserId($command->userUuid);
        $user = $this->userRepository->findByUuid($userId);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        if ($user->getGroupId() !== null) {
            throw new UserAlreadyInGroupException();
        }

        $existing = $this->invitationRepository->findPendingByUserAndGroup(
            $userId,
            $groupId,
            GroupInvitation::TYPE_REQUEST,
        );

        if ($existing !== null) {
            throw new GroupInvitationAlreadyExistsException();
        }

        $request = GroupInvitation::request(
            groupUuid: $groupId,
            userUuid: $userId,
        );

        $this->invitationRepository->save($request);
    }
}
