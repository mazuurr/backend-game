<?php

declare(strict_types=1);

namespace App\Campaign\Domain\Entity;

use App\Campaign\Domain\ValueObject\CampaignId;
use App\Campaign\Domain\ValueObject\CampaignName;

class Campaign
{
    private int $id;
    private CampaignId $uuid;
    private CampaignName $name;
    private ?string $description;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct(
        CampaignId $uuid,
        CampaignName $name,
        ?string $description,
    ) {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->description = $description;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = null;
    }

    public static function create(
        CampaignId $uuid,
        CampaignName $name,
        ?string $description = null,
    ): self {
        return new self($uuid, $name, $description);
    }

    public function update(?CampaignName $name, ?string $description, bool $clearDescription = false): void
    {
        if ($name !== null) {
            $this->name = $name;
        }
        if ($clearDescription) {
            $this->description = null;
        } elseif ($description !== null) {
            $this->description = $description;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getUuid(): CampaignId { return $this->uuid; }
    public function getName(): CampaignName { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
}
