<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class PuzzleNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Puzzle not found.', self::HTTP_NOT_FOUND);
    }
}
