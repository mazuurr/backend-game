<?php

declare(strict_types=1);

namespace App\Group\Domain\Exception;

final class GroupNameAlreadyExistsException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('A group with this name already exists.');
    }
}
