<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Entity;

use App\Group\Domain\ValueObject\GroupId;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\User\Domain\ValueObject\UserId;

class PuzzleSession
{
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_GROUP = 'group';

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    public const LIFETIME_HOURS = 24;

    private int $id;
    private PuzzleSessionId $uuid;
    private PuzzleId $puzzleUuid;
    private string $visibility;
    private ?GroupId $groupUuid;
    private UserId $createdByUserUuid;
    private string $status;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $expiresAt;
    private ?\DateTimeImmutable $closedAt;

    private function __construct(
        PuzzleSessionId $uuid,
        PuzzleId $puzzleUuid,
        string $visibility,
        ?GroupId $groupUuid,
        UserId $createdByUserUuid,
    ) {
        if (!in_array($visibility, [self::VISIBILITY_PUBLIC, self::VISIBILITY_GROUP], true)) {
            throw new \InvalidArgumentException(sprintf('Invalid visibility: %s', $visibility));
        }

        if ($visibility === self::VISIBILITY_GROUP && $groupUuid === null) {
            throw new \InvalidArgumentException('A group session requires a group.');
        }

        $this->uuid = $uuid;
        $this->puzzleUuid = $puzzleUuid;
        $this->visibility = $visibility;
        $this->groupUuid = $visibility === self::VISIBILITY_GROUP ? $groupUuid : null;
        $this->createdByUserUuid = $createdByUserUuid;
        $this->status = self::STATUS_OPEN;
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $this->createdAt->modify(sprintf('+%d hours', self::LIFETIME_HOURS));
        $this->closedAt = null;
    }

    public static function start(
        PuzzleSessionId $uuid,
        PuzzleId $puzzleUuid,
        string $visibility,
        ?GroupId $groupUuid,
        UserId $createdByUserUuid,
    ): self {
        return new self($uuid, $puzzleUuid, $visibility, $groupUuid, $createdByUserUuid);
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

    public function isExpiredAt(\DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }

    public function getId(): int { return $this->id; }
    public function getUuid(): PuzzleSessionId { return $this->uuid; }
    public function getPuzzleUuid(): PuzzleId { return $this->puzzleUuid; }
    public function getVisibility(): string { return $this->visibility; }
    public function getGroupUuid(): ?GroupId { return $this->groupUuid; }
    public function getCreatedByUserUuid(): UserId { return $this->createdByUserUuid; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function getClosedAt(): ?\DateTimeImmutable { return $this->closedAt; }
}
