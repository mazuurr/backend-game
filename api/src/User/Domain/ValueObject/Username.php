<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

final class Username
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if (mb_strlen($value) < 3 || mb_strlen($value) > 50) {
            throw new \InvalidArgumentException('Username must be between 3 and 50 characters.');
        }

        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $value)) {
            throw new \InvalidArgumentException('Username can only contain letters, numbers, underscores, hyphens and dots.');
        }

        $this->value = $value;
    }

    public static function generateFromEmail(Email $email): self
    {
        $local = explode('@', $email->value())[0];
        $base = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $local);

        if (mb_strlen($base) < 3) {
            $base .= '_' . bin2hex(random_bytes(2));
        }

        // Dodaj losowy suffix żeby uniknąć kolizji
        $suffix = '_' . bin2hex(random_bytes(3));

        return new self(mb_substr($base, 0, 50 - mb_strlen($suffix)) . $suffix);
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
