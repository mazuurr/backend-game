<?php

declare(strict_types=1);

namespace App\Group\Application\Command\RejectGroupInvitationByAdmin;

final class RejectGroupInvitationByAdminCommand
{
    public function __construct(
        public readonly string $invitationUuid,
    ) {}
}
