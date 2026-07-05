<?php

declare(strict_types=1);

namespace App\Group\Application\Command\AcceptGroupInvitation;

final class AcceptGroupInvitationCommand
{
    public function __construct(
        public readonly string $invitationUuid,
        public readonly string $actorUuid,
    ) {}
}
