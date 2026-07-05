<?php

declare(strict_types=1);

namespace App\Campaign\Application\DTO;

use App\Campaign\Domain\Entity\Campaign;
use App\Puzzle\Application\DTO\PuzzleDTO;

final class CampaignDTO implements \JsonSerializable
{
    /** @param PuzzleDTO[] $puzzles */
    public function __construct(
        public readonly string $uuid,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $createdAt,
        public readonly ?string $updatedAt,
        public readonly array $puzzles = [],
    ) {}

    public static function fromEntity(Campaign $campaign, array $puzzles = []): self
    {
        return new self(
            uuid: $campaign->getUuid()->value(),
            name: $campaign->getName()->value(),
            description: $campaign->getDescription(),
            createdAt: $campaign->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $campaign->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            puzzles: $puzzles,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'puzzles' => $this->puzzles,
        ];
    }
}
