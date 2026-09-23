<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Entity;

use App\Puzzle\Domain\ValueObject\PuzzleId;

class Puzzle
{
    private int $id;
    private PuzzleId $uuid;
    private string $originalName;
    private string $storedFilename;
    private string $mimeType;
    private int $size;
    private ?int $difficulty;
    private ?int $totalPieces;
    private ?int $piecesPerFragment;
    private ?int $piecesX;
    private ?int $piecesY;
    private \DateTimeImmutable $createdAt;

    private function __construct(
        PuzzleId $uuid,
        string $originalName,
        string $storedFilename,
        string $mimeType,
        int $size,
        ?int $difficulty,
        ?int $totalPieces,
        ?int $piecesPerFragment,
        ?int $piecesX,
        ?int $piecesY,
    ) {
        $this->uuid = $uuid;
        $this->originalName = $originalName;
        $this->storedFilename = $storedFilename;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->difficulty = $difficulty;
        $this->totalPieces = $totalPieces;
        $this->piecesPerFragment = $piecesPerFragment;
        $this->piecesX = $piecesX;
        $this->piecesY = $piecesY;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(
        PuzzleId $uuid,
        string $originalName,
        string $storedFilename,
        string $mimeType,
        int $size,
        ?int $difficulty = null,
        ?int $totalPieces = null,
        ?int $piecesPerFragment = null,
        ?int $piecesX = null,
        ?int $piecesY = null,
    ): self {
        return new self($uuid, $originalName, $storedFilename, $mimeType, $size, $difficulty, $totalPieces, $piecesPerFragment, $piecesX, $piecesY);
    }

    public function changeDifficulty(?int $difficulty): void
    {
        $this->difficulty = $difficulty;
    }

    public function changeTotalPieces(?int $totalPieces): void
    {
        $this->totalPieces = $totalPieces;
    }

    public function changePiecesPerFragment(?int $piecesPerFragment): void
    {
        $this->piecesPerFragment = $piecesPerFragment;
    }

    public function changePiecesX(?int $piecesX): void
    {
        $this->piecesX = $piecesX;
    }

    public function changePiecesY(?int $piecesY): void
    {
        $this->piecesY = $piecesY;
    }

    public function getId(): int { return $this->id; }
    public function getUuid(): PuzzleId { return $this->uuid; }
    public function getOriginalName(): string { return $this->originalName; }
    public function getStoredFilename(): string { return $this->storedFilename; }
    public function getMimeType(): string { return $this->mimeType; }
    public function getSize(): int { return $this->size; }
    public function getDifficulty(): ?int { return $this->difficulty; }
    public function getTotalPieces(): ?int { return $this->totalPieces; }
    public function getPiecesPerFragment(): ?int { return $this->piecesPerFragment; }
    public function getPiecesX(): ?int { return $this->piecesX; }
    public function getPiecesY(): ?int { return $this->piecesY; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getEffectivePieceCount(): int
    {
        if ($this->piecesX !== null && $this->piecesY !== null) {
            return $this->piecesX * $this->piecesY;
        }

        return $this->totalPieces ?? 0;
    }
}
