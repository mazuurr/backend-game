<?php

declare(strict_types=1);

namespace App\Campaign\Domain\Exception;

final class CampaignNameAlreadyExistsException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Campaign with this name already exists.');
    }
}
