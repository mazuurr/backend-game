<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetCurrentUser;

final class GetCurrentUserQuery
{
    public function __construct(
        public readonly string $uuid,
    ) {}
}
