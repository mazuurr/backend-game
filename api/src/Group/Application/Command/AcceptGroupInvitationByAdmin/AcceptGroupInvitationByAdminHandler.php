<?php

declare(strict_types=1);

namespace App\Group\Application\Command\AcceptGroupInvitationByAdmin;

use App\Group\Domain\Exception\GroupInvitationNotFoundException;
use App\Group\Domain\Exception\GroupInvitationNotPendingException;
use App\Group\Domain\Repository\GroupInvitationRepositoryInterface;
use App\Group\Domain\ValueObject\GroupInvitationId;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class AcceptGroupInvitationByAdminHandler
{
    public function __construct(
        private readonly GroupInvitationRepositoryInterface $invitationRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(AcceptGroupInvitationByAdminCommand $command): void
    {
        $invitation = $this->invitationRepository->findByUuid(new GroupInvitationId($command->invitationUuid));

        if ($invitation === null) {
            throw new GroupInvitationNotFoundException();
        }

        if (!$invitation->isPending()) {
            throw new GroupInvitationNotPendingException();
        }

        $invitation->accept();

        $user = $this->userRepository->findByUuid($invitation->getUserUuid());

        if ($user !== null) {
            $user->assignToGroup($invitation->getGroupUuid());
            $this->userRepository->save($user);
        }

        $this->invitationRepository->save($invitation);
    }
}
