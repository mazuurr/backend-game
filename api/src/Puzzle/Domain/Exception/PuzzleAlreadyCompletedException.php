<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class PuzzleAlreadyCompletedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            'You have already completed this puzzle.',
            self::HTTP_FORBIDDEN,
        );
    }
}
