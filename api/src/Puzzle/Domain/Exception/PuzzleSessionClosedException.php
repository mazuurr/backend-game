<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class PuzzleSessionClosedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Puzzle session is closed.', self::HTTP_CONFLICT);
    }
}
