<?php

declare(strict_types=1);

namespace App\Group\Domain\Exception;

final class UserAlreadyInGroupException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('User already belongs to a group.');
    }
}
