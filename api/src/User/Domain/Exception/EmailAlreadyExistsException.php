<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class EmailAlreadyExistsException extends DomainException
{
    public function __construct(string $message = 'Email already exists.')
    {
        parent::__construct($message, self::HTTP_CONFLICT);
    }
}
