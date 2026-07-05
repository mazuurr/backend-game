<?php

declare(strict_types=1);

namespace App\Group\Application\Query\GetGroup;

final class GetGroupQuery
{
    public function __construct(
        public readonly string $uuid,
    ) {}
}
