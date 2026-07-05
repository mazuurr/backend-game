<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetMyProgress;

final class GetMyProgressQuery
{
    public function __construct(
        public readonly string $userUuid,
    ) {}
}
