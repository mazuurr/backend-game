<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class InvalidCurrentPasswordException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Current password is incorrect.');
    }
}
