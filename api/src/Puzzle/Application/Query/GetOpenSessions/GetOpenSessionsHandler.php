<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetOpenSessions;

use App\Puzzle\Application\DTO\SessionDTO;
use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetOpenSessionsHandler
{
    public function __construct(
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /** @return SessionDTO[] */
    public function __invoke(GetOpenSessionsQuery $query): array
    {
        $requester = $this->userRepository->findByUuid(new UserId($query->requesterUserUuid));
        $requesterGroupUuid = $requester?->getGroupId()?->value();

        $visible = array_filter(
            $this->sessionRepository->findOpen(),
            static function (PuzzleSession $session) use ($requesterGroupUuid): bool {
                if ($session->isPublic()) {
                    return true;
                }

                // Group session: only members of that group may see it.
                return $requesterGroupUuid !== null
                    && $session->getGroupUuid()?->value() === $requesterGroupUuid;
            },
        );

        return array_map(
            static fn (PuzzleSession $session) => SessionDTO::fromEntity($session),
            array_values($visible),
        );
    }
}
