<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetSession;

final class GetSessionQuery
{
    public function __construct(
        public readonly string $sessionUuid,
    ) {}
}
