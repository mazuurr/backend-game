<?php

declare(strict_types=1);

namespace App\Group\Infrastructure\Persistence\Doctrine\Repository;

use App\Group\Domain\Entity\GroupInvitation;
use App\Group\Domain\Repository\GroupInvitationRepositoryInterface;
use App\Group\Domain\ValueObject\GroupId;
use App\Group\Domain\ValueObject\GroupInvitationId;
use App\User\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineGroupInvitationRepository implements GroupInvitationRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(GroupInvitation $invitation): void
    {
        $this->em->persist($invitation);
        $this->em->flush();
    }

    public function findByUuid(GroupInvitationId $uuid): ?GroupInvitation
    {
        return $this->em->createQueryBuilder()
            ->select('i')
            ->from(GroupInvitation::class, 'i')
            ->where('i.uuid = :uuid')
            ->setParameter('uuid', $uuid->value())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return GroupInvitation[] */
    public function findByUser(UserId $userUuid): array
    {
        return $this->em->createQueryBuilder()
            ->select('i')
            ->from(GroupInvitation::class, 'i')
            ->where('i.userUuid = :userUuid')
            ->andWhere('i.status = :status')
            ->setParameter('userUuid', $userUuid->value())
            ->setParameter('status', GroupInvitation::STATUS_PENDING)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return GroupInvitation[] */
    public function findByGroup(GroupId $groupUuid): array
    {
        return $this->em->createQueryBuilder()
            ->select('i')
            ->from(GroupInvitation::class, 'i')
            ->where('i.groupUuid = :groupUuid')
            ->andWhere('i.status = :status')
            ->setParameter('groupUuid', $groupUuid->value())
            ->setParameter('status', GroupInvitation::STATUS_PENDING)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingByUserAndGroup(UserId $userUuid, GroupId $groupUuid, string $type): ?GroupInvitation
    {
        return $this->em->createQueryBuilder()
            ->select('i')
            ->from(GroupInvitation::class, 'i')
            ->where('i.userUuid = :userUuid')
            ->andWhere('i.groupUuid = :groupUuid')
            ->andWhere('i.type = :type')
            ->andWhere('i.status = :status')
            ->setParameter('userUuid', $userUuid->value())
            ->setParameter('groupUuid', $groupUuid->value())
            ->setParameter('type', $type)
            ->setParameter('status', GroupInvitation::STATUS_PENDING)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
