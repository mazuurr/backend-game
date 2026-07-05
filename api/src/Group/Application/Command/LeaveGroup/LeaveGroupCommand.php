<?php

declare(strict_types=1);

namespace App\Group\Application\Command\LeaveGroup;

final class LeaveGroupCommand
{
    public function __construct(
        public readonly string $userUuid,
    ) {}
}
