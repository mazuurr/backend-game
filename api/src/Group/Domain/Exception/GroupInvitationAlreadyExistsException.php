<?php

declare(strict_types=1);

namespace App\Group\Domain\Exception;

final class GroupInvitationAlreadyExistsException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('A pending invitation or request already exists.');
    }
}
