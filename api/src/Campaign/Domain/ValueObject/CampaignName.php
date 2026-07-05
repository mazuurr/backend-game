<?php

declare(strict_types=1);

namespace App\Campaign\Domain\ValueObject;

final class CampaignName
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Campaign name cannot be empty.');
        }
        if (mb_strlen($value) > 100) {
            throw new \InvalidArgumentException('Campaign name cannot exceed 100 characters.');
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
