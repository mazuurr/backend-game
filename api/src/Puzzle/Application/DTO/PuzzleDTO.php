<?php

declare(strict_types=1);

namespace App\Puzzle\Application\DTO;

use App\Puzzle\Domain\Entity\Puzzle;

final class PuzzleDTO implements \JsonSerializable
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $originalName,
        public readonly string $storedFilename,
        public readonly string $mimeType,
        public readonly int $size,
        public readonly ?string $campaignUuid,
        public readonly ?int $difficulty,
        public readonly ?int $totalPieces,
        public readonly ?int $piecesPerFragment,
        public readonly ?int $piecesX,
        public readonly ?int $piecesY,
        public readonly string $createdAt,
    ) {}

    public static function fromEntity(Puzzle $puzzle): self
    {
        return new self(
            uuid: $puzzle->getUuid()->value(),
            originalName: $puzzle->getOriginalName(),
            storedFilename: $puzzle->getStoredFilename(),
            mimeType: $puzzle->getMimeType(),
            size: $puzzle->getSize(),
            campaignUuid: $puzzle->getCampaignUuid()?->value(),
            difficulty: $puzzle->getDifficulty(),
            totalPieces: $puzzle->getTotalPieces(),
            piecesPerFragment: $puzzle->getPiecesPerFragment(),
            piecesX: $puzzle->getPiecesX(),
            piecesY: $puzzle->getPiecesY(),
            createdAt: $puzzle->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
            'campaign_uuid' => $this->campaignUuid,
            'difficulty' => $this->difficulty,
            'total_pieces' => $this->totalPieces,
            'pieces_per_fragment' => $this->piecesPerFragment,
            'pieces_x' => $this->piecesX,
            'pieces_y' => $this->piecesY,
            'created_at' => $this->createdAt,
        ];
    }
}
