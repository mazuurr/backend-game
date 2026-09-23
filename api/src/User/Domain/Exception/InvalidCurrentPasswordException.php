<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidCurrentPasswordException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Current password is incorrect.', self::HTTP_BAD_REQUEST);
    }
}
