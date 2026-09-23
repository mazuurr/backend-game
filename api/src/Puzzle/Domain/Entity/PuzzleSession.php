<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Entity;

use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\Shared\Domain\ValueObject\UserId;

class PuzzleSession
{
    public const VISIBILITY_PUBLIC = 'public';

    public const MODE_INDIVIDUAL = 'individual';
    public const MODE_SHARED = 'shared';

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    public const LIFETIME_MODIFIER_REGULAR = '+1 month';

    private int $id;
    private PuzzleSessionId $uuid;
    private PuzzleId $puzzleUuid;
    private string $visibility;
    private string $mode;
    private UserId $createdByUserUuid;
    private string $status;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $expiresAt;
    private ?\DateTimeImmutable $closedAt;

    private function __construct(
        PuzzleSessionId $uuid,
        PuzzleId $puzzleUuid,
        string $visibility,
        string $mode,
        UserId $createdByUserUuid,
    ) {
        if ($visibility !== self::VISIBILITY_PUBLIC) {
            throw new \InvalidArgumentException(sprintf('Invalid visibility: %s', $visibility));
        }

        if (!in_array($mode, [self::MODE_INDIVIDUAL, self::MODE_SHARED], true)) {
            throw new \InvalidArgumentException(sprintf('Invalid mode: %s', $mode));
        }

        $this->uuid = $uuid;
        $this->puzzleUuid = $puzzleUuid;
        $this->visibility = $visibility;
        $this->mode = $mode;
        $this->createdByUserUuid = $createdByUserUuid;
        $this->status = self::STATUS_OPEN;
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $this->createdAt->modify(self::LIFETIME_MODIFIER_REGULAR);
        $this->closedAt = null;
    }

    public static function start(
        PuzzleSessionId $uuid,
        PuzzleId $puzzleUuid,
        string $visibility,
        UserId $createdByUserUuid,
        string $mode = self::MODE_INDIVIDUAL,
    ): self {
        return new self($uuid, $puzzleUuid, $visibility, $mode, $createdByUserUuid);
    }

    public function close(): void
    {
        if ($this->status === self::STATUS_CLOSED) {
            return;
        }

        $this->status = self::STATUS_CLOSED;
        $this->closedAt = new \DateTimeImmutable();
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isPublic(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    public function isIndividual(): bool
    {
        return $this->mode === self::MODE_INDIVIDUAL;
    }

    public function isShared(): bool
    {
        return $this->mode === self::MODE_SHARED;
    }

    public function isExpiredAt(\DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }

    public function getId(): int { return $this->id; }
    public function getUuid(): PuzzleSessionId { return $this->uuid; }
    public function getPuzzleUuid(): PuzzleId { return $this->puzzleUuid; }
    public function getVisibility(): string { return $this->visibility; }
    public function getMode(): string { return $this->mode; }
    public function getCreatedByUserUuid(): UserId { return $this->createdByUserUuid; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function getClosedAt(): ?\DateTimeImmutable { return $this->closedAt; }
}
