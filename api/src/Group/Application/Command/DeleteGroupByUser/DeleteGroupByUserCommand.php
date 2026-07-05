<?php

declare(strict_types=1);

namespace App\Group\Application\Command\DeleteGroupByUser;

final class DeleteGroupByUserCommand
{
    public function __construct(
        public readonly string $groupUuid,
        public readonly string $requesterUuid,
    ) {}
}
