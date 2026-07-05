<?php

declare(strict_types=1);

namespace App\Group\Application\Command\DeleteGroup;

final class DeleteGroupCommand
{
    public function __construct(
        public readonly string $uuid,
    ) {}
}
