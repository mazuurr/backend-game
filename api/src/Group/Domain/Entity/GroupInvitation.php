<?php

declare(strict_types=1);

namespace App\Group\Domain\Entity;

use App\Group\Domain\ValueObject\GroupId;
use App\Group\Domain\ValueObject\GroupInvitationId;
use App\User\Domain\ValueObject\UserId;

class GroupInvitation
{
    public const TYPE_INVITATION = 'invitation'; // group invites user
    public const TYPE_REQUEST    = 'request';    // user requests to join

    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    private int $id;
    private GroupInvitationId $uuid;
    private GroupId $groupUuid;
    private UserId $userUuid;
    private string $type;
    private string $status;
    private UserId $initiatorUuid;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $respondedAt;

    private function __construct(
        GroupInvitationId $uuid,
        GroupId $groupUuid,
        UserId $userUuid,
        string $type,
        UserId $initiatorUuid,
    ) {
        $this->uuid = $uuid;
        $this->groupUuid = $groupUuid;
        $this->userUuid = $userUuid;
        $this->type = $type;
        $this->status = self::STATUS_PENDING;
        $this->initiatorUuid = $initiatorUuid;
        $this->createdAt = new \DateTimeImmutable();
        $this->respondedAt = null;
    }

    public static function invite(
        GroupId $groupUuid,
        UserId $userUuid,
        UserId $inviterUuid,
    ): self {
        return new self(
            uuid: GroupInvitationId::generate(),
            groupUuid: $groupUuid,
            userUuid: $userUuid,
            type: self::TYPE_INVITATION,
            initiatorUuid: $inviterUuid,
        );
    }

    public static function request(
        GroupId $groupUuid,
        UserId $userUuid,
    ): self {
        return new self(
            uuid: GroupInvitationId::generate(),
            groupUuid: $groupUuid,
            userUuid: $userUuid,
            type: self::TYPE_REQUEST,
            initiatorUuid: $userUuid,
        );
    }

    public function accept(): void
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->respondedAt = new \DateTimeImmutable();
    }

    public function reject(): void
    {
        $this->status = self::STATUS_REJECTED;
        $this->respondedAt = new \DateTimeImmutable();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInvitation(): bool
    {
        return $this->type === self::TYPE_INVITATION;
    }

    public function isRequest(): bool
    {
        return $this->type === self::TYPE_REQUEST;
    }

    public function getId(): int { return $this->id; }
    public function getUuid(): GroupInvitationId { return $this->uuid; }
    public function getGroupUuid(): GroupId { return $this->groupUuid; }
    public function getUserUuid(): UserId { return $this->userUuid; }
    public function getType(): string { return $this->type; }
    public function getStatus(): string { return $this->status; }
    public function getInitiatorUuid(): UserId { return $this->initiatorUuid; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getRespondedAt(): ?\DateTimeImmutable { return $this->respondedAt; }
}
