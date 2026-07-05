<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetBoardState;

final class GetBoardStateQuery
{
    public function __construct(
        public readonly string $sessionUuid,
    ) {}
}
