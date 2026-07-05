<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Exception;

final class NotGroupMemberException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('You are not a member of the group that owns this session.');
    }
}
