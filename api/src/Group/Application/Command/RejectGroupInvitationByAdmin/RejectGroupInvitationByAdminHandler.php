<?php

declare(strict_types=1);

namespace App\Group\Application\Command\RejectGroupInvitationByAdmin;

use App\Group\Domain\Exception\GroupInvitationNotFoundException;
use App\Group\Domain\Exception\GroupInvitationNotPendingException;
use App\Group\Domain\Repository\GroupInvitationRepositoryInterface;
use App\Group\Domain\ValueObject\GroupInvitationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RejectGroupInvitationByAdminHandler
{
    public function __construct(
        private readonly GroupInvitationRepositoryInterface $invitationRepository,
    ) {}

    public function __invoke(RejectGroupInvitationByAdminCommand $command): void
    {
        $invitation = $this->invitationRepository->findByUuid(new GroupInvitationId($command->invitationUuid));

        if ($invitation === null) {
            throw new GroupInvitationNotFoundException();
        }

        if (!$invitation->isPending()) {
            throw new GroupInvitationNotPendingException();
        }

        $invitation->reject();
        $this->invitationRepository->save($invitation);
    }
}
