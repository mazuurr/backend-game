<?php

declare(strict_types=1);

namespace App\Group\Application\Query\GetMyGroupInvitations;

use App\Group\Application\DTO\GroupInvitationDTO;
use App\Group\Domain\Repository\GroupInvitationRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetMyGroupInvitationsHandler
{
    public function __construct(
        private readonly GroupInvitationRepositoryInterface $invitationRepository,
    ) {}

    /** @return GroupInvitationDTO[] */
    public function __invoke(GetMyGroupInvitationsQuery $query): array
    {
        $invitations = $this->invitationRepository->findByUser(new UserId($query->userUuid));

        return array_map(
            static fn ($i) => GroupInvitationDTO::fromEntity($i),
            $invitations,
        );
    }
}
