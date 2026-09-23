<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetMySessions;

final class GetMySessionsQuery
{
    public function __construct(
        public readonly string $requesterUserUuid,
        public readonly ?string $mode = null,
        public readonly ?string $status = null,
    ) {}
}
