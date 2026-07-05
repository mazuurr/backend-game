<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Controller;

use App\Puzzle\Application\Command\ClosePuzzleSession\ClosePuzzleSessionCommand;
use App\Puzzle\Application\Command\StartPuzzleSession\StartPuzzleSessionCommand;
use App\Puzzle\Application\Query\GetBoardState\GetBoardStateQuery;
use App\Puzzle\Application\Query\GetOpenSessions\GetOpenSessionsQuery;
use App\Puzzle\Application\Query\GetSession\GetSessionQuery;
use App\Puzzle\Application\Query\GetSessionMoves\GetSessionMovesQuery;
use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserSessionController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {}

    #[Route('/api/puzzles/{uuid}/sessions', name: 'user_session_start', methods: ['POST'])]
    public function start(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        $visibility = (string) ($data['visibility'] ?? PuzzleSession::VISIBILITY_PUBLIC);
        $groupUuid = $data['group_uuid'] ?? null;

        if (!in_array($visibility, [PuzzleSession::VISIBILITY_PUBLIC, PuzzleSession::VISIBILITY_GROUP], true)) {
            return new JsonResponse(['error' => 'visibility must be "public" or "group".'], Response::HTTP_BAD_REQUEST);
        }

        if ($visibility === PuzzleSession::VISIBILITY_GROUP && empty($groupUuid)) {
            return new JsonResponse(['error' => 'group_uuid is required for a group session.'], Response::HTTP_BAD_REQUEST);
        }

        $sessionId = PuzzleSessionId::generate();

        try {
            $this->commandBus->dispatch(new StartPuzzleSessionCommand(
                sessionUuid: $sessionId->value(),
                puzzleUuid: $uuid,
                createdByUserUuid: $this->getSecurityUser()->getUuid(),
                visibility: $visibility,
                groupUuid: $groupUuid !== null ? (string) $groupUuid : null,
            ));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        $session = $this->queryBus->ask(new GetSessionQuery($sessionId->value()));

        return new JsonResponse(['data' => $session], Response::HTTP_CREATED);
    }

    #[Route('/api/sessions', name: 'user_session_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $sessions = $this->queryBus->ask(new GetOpenSessionsQuery(
            requesterUserUuid: $this->getSecurityUser()->getUuid(),
        ));

        return new JsonResponse(['data' => $sessions]);
    }

    #[Route('/api/sessions/{sessionUuid}', name: 'user_session_show', methods: ['GET'])]
    public function show(string $sessionUuid): JsonResponse
    {
        try {
            $session = $this->queryBus->ask(new GetSessionQuery($sessionUuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $session]);
    }

    #[Route('/api/sessions/{sessionUuid}/board', name: 'user_session_board', methods: ['GET'])]
    public function board(string $sessionUuid): JsonResponse
    {
        try {
            $board = $this->queryBus->ask(new GetBoardStateQuery($sessionUuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $board]);
    }

    #[Route('/api/sessions/{sessionUuid}/moves', name: 'user_session_moves', methods: ['GET'])]
    public function moves(string $sessionUuid): JsonResponse
    {
        try {
            $moves = $this->queryBus->ask(new GetSessionMovesQuery($sessionUuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $moves]);
    }

    #[Route('/api/sessions/{sessionUuid}/close', name: 'user_session_close', methods: ['POST'])]
    public function close(string $sessionUuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new ClosePuzzleSessionCommand(
                sessionUuid: $sessionUuid,
                requestedByUserUuid: $this->getSecurityUser()->getUuid(),
            ));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['message' => 'Session closed.']);
    }

    private function getSecurityUser(): SecurityUser
    {
        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new \RuntimeException('User not authenticated.');
        }

        return $user;
    }
}
