<?php

declare(strict_types=1);

namespace App\Puzzle\Application\DTO;

final class SessionContributionsDTO implements \JsonSerializable
{
    /** @param UserContributionDTO[] $contributions ranked, highest first */
    public function __construct(
        public readonly int $total,
        public readonly int $correct,
        public readonly array $contributions,
    ) {}

    public function leader(): ?UserContributionDTO
    {
        return $this->contributions[0] ?? null;
    }

    public function jsonSerialize(): array
    {
        return [
            'total' => $this->total,
            'correct' => $this->correct,
            'leader' => $this->leader(),
            'contributions' => $this->contributions,
        ];
    }
}
