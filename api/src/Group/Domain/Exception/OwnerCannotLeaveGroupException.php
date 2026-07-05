<?php

declare(strict_types=1);

namespace App\Group\Domain\Exception;

final class OwnerCannotLeaveGroupException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('You are the owner of this group. Transfer ownership before leaving.');
    }
}
