<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class UserNotFoundException extends DomainException
{
    public function __construct(string $message = 'User not found.')
    {
        parent::__construct($message, self::HTTP_NOT_FOUND);
    }
}
