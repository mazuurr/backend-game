<?php

declare(strict_types=1);

namespace App\Group\Application\Command\TransferOwnership;

final class TransferOwnershipCommand
{
    public function __construct(
        public readonly string $groupUuid,
        public readonly string $requesterUuid,
        public readonly string $newOwnerUuid,
    ) {}
}
