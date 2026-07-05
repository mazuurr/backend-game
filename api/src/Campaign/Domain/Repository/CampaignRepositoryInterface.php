<?php

declare(strict_types=1);

namespace App\Campaign\Domain\Repository;

use App\Campaign\Domain\Entity\Campaign;
use App\Campaign\Domain\ValueObject\CampaignId;
use App\Campaign\Domain\ValueObject\CampaignName;

interface CampaignRepositoryInterface
{
    public function save(Campaign $campaign): void;

    public function remove(Campaign $campaign): void;

    public function findByUuid(CampaignId $uuid): ?Campaign;

    /** @return Campaign[] */
    public function findAll(): array;

    /** @return Campaign[] */
    public function findPaginated(int $offset, int $limit, ?string $search): array;

    public function countFiltered(?string $search): int;

    public function nameExists(CampaignName $name): bool;

    public function nameExistsExcluding(CampaignName $name, CampaignId $excludeUuid): bool;
}
