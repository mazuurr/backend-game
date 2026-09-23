<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class UserAlreadyActiveException extends DomainException
{
    public function __construct(string $message = 'User is already active.')
    {
        parent::__construct($message, self::HTTP_CONFLICT);
    }
}
