<?php

declare(strict_types=1);

namespace App\Group\Application\Query\GetGroupInvitations;

use App\Group\Application\DTO\GroupInvitationDTO;
use App\Group\Domain\Exception\GroupNotFoundException;
use App\Group\Domain\Repository\GroupInvitationRepositoryInterface;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetGroupInvitationsHandler
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly GroupInvitationRepositoryInterface $invitationRepository,
    ) {}

    /** @return GroupInvitationDTO[] */
    public function __invoke(GetGroupInvitationsQuery $query): array
    {
        $groupId = new GroupId($query->groupUuid);

        if ($this->groupRepository->findByUuid($groupId) === null) {
            throw new GroupNotFoundException();
        }

        $invitations = $this->invitationRepository->findByGroup($groupId);

        return array_map(
            static fn ($i) => GroupInvitationDTO::fromEntity($i),
            $invitations,
        );
    }
}
