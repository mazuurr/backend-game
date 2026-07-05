<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Repository;

use App\Campaign\Domain\ValueObject\CampaignId;
use App\Puzzle\Domain\Entity\Puzzle;
use App\Puzzle\Domain\ValueObject\PuzzleId;

interface PuzzleRepositoryInterface
{
    public function save(Puzzle $puzzle): void;

    public function remove(Puzzle $puzzle): void;

    public function findByUuid(PuzzleId $uuid): ?Puzzle;

    /** @return Puzzle[] */
    public function findAll(): array;

    /** @return Puzzle[] */
    public function findPaginated(int $offset, int $limit, ?string $campaignUuid): array;

    public function countFiltered(?string $campaignUuid): int;

    /** @return Puzzle[] */
    public function findByCampaignId(CampaignId $campaignId): array;
}
