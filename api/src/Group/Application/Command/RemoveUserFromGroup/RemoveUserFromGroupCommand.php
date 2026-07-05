<?php

declare(strict_types=1);

namespace App\Group\Application\Command\RemoveUserFromGroup;

final class RemoveUserFromGroupCommand
{
    public function __construct(
        public readonly string $groupUuid,
        public readonly string $userUuid,
    ) {}
}
