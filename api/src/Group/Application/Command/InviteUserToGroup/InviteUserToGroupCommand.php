<?php

declare(strict_types=1);

namespace App\Group\Application\Command\InviteUserToGroup;

final class InviteUserToGroupCommand
{
    public function __construct(
        public readonly string $groupUuid,
        public readonly string $inviterUuid,
        public readonly string $inviteeUuid,
    ) {}
}
