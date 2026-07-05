<?php

declare(strict_types=1);

namespace App\Group\Domain\Exception;

final class UserNotInGroupException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('The new owner must be a member of the group.');
    }
}
