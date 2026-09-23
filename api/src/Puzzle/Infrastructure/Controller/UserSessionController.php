<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Controller;

use App\Puzzle\Application\Command\ClosePuzzleSession\ClosePuzzleSessionCommand;
use App\Puzzle\Application\Command\StartPuzzleSession\StartPuzzleSessionCommand;
use App\Puzzle\Application\Query\GetBoardState\GetBoardStateQuery;
use App\Puzzle\Application\Query\GetMySessions\GetMySessionsQuery;
use App\Puzzle\Application\Query\GetMySessionStats\GetMySessionStatsQuery;
use App\Puzzle\Application\Query\GetOpenSessions\GetOpenSessionsQuery;
use App\Puzzle\Application\Query\GetSession\GetSessionQuery;
use App\Puzzle\Application\Query\GetSessionContributions\GetSessionContributionsQuery;
use App\Puzzle\Application\Query\GetSessionMoves\GetSessionMovesQuery;
use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use App\User\Infrastructure\Security\SecurityUser;
use Nelmio\ApiDocBundle\Attribute\Security as ApiDocSecurity;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Sessions')]
#[ApiDocSecurity(name: 'Bearer')]
final class UserSessionController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {}

    #[Route('/api/puzzles/{uuid}/sessions', name: 'user_session_start', methods: ['POST'])]
    #[OA\Parameter(name: 'uuid', description: 'Puzzle UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\RequestBody(
        description: 'Session settings. Empty body is allowed (defaults apply).',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'visibility', type: 'string', enum: ['public'], example: 'public', nullable: true, description: 'Defaults to "public"'),
                new OA\Property(property: 'mode', type: 'string', enum: ['individual', 'shared'], example: 'individual', nullable: true, description: 'Defaults to "individual"'),
            ],
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'Session started',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
                new OA\Property(property: 'puzzle_uuid', type: 'string', format: 'uuid'),
                new OA\Property(property: 'visibility', type: 'string', enum: ['public'], example: 'public'),
                new OA\Property(property: 'mode', type: 'string', enum: ['individual', 'shared'], example: 'individual'),
                new OA\Property(property: 'created_by_user_uuid', type: 'string', format: 'uuid'),
                new OA\Property(property: 'status', type: 'string', enum: ['open', 'closed'], example: 'open'),
                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'closed_at', type: 'string', format: 'date-time', nullable: true),
                new OA\Property(property: 'total_pieces', type: 'integer', nullable: true),
                new OA\Property(property: 'correct_pieces', type: 'integer', nullable: true),
                new OA\Property(property: 'leader', type: 'object', nullable: true),
            ], type: 'object')],
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid visibility or mode',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'visibility must be "public".')]),
    )]
    #[OA\Response(
        response: 404,
        description: 'Puzzle not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Puzzle not found.')]),
    )]
    #[OA\Response(
        response: 403,
        description: 'The user already completed this puzzle',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'You have already completed this puzzle.')]),
    )]
    public function start(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        $visibility = (string) ($data['visibility'] ?? PuzzleSession::VISIBILITY_PUBLIC);
        if ($visibility !== PuzzleSession::VISIBILITY_PUBLIC) {
            return new JsonResponse(['error' => 'visibility must be "public".'], Response::HTTP_BAD_REQUEST);
        }

        $mode = (string) ($data['mode'] ?? PuzzleSession::MODE_INDIVIDUAL);

        if (!in_array($mode, [PuzzleSession::MODE_INDIVIDUAL, PuzzleSession::MODE_SHARED], true)) {
            return new JsonResponse(['error' => 'mode must be "individual" or "shared".'], Response::HTTP_BAD_REQUEST);
        }

        $sessionId = PuzzleSessionId::generate();

        $this->commandBus->dispatch(new StartPuzzleSessionCommand(
            sessionUuid: $sessionId->value(),
            puzzleUuid: $uuid,
            createdByUserUuid: $this->getSecurityUser()->getUuid(),
            visibility: $visibility,
            mode: $mode,
        ));

        $session = $this->queryBus->ask(new GetSessionQuery($sessionId->value()));

        return new JsonResponse(['data' => $session], Response::HTTP_CREATED);
    }

    #[Route('/api/sessions', name: 'user_session_list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Open public sessions visible to the current user',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                properties: [
                    new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'puzzle_uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'visibility', type: 'string', enum: ['public'], example: 'public'),
                    new OA\Property(property: 'mode', type: 'string', enum: ['individual', 'shared'], example: 'individual'),
                    new OA\Property(property: 'created_by_user_uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'status', type: 'string', enum: ['open', 'closed'], example: 'open'),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'closed_at', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'total_pieces', type: 'integer', nullable: true, example: 500),
                    new OA\Property(property: 'correct_pieces', type: 'integer', nullable: true, example: 120),
                    new OA\Property(property: 'leader', nullable: true, properties: [
                        new OA\Property(property: 'user_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'username', type: 'string', example: 'player1'),
                        new OA\Property(property: 'correct_count', type: 'integer', example: 40),
                        new OA\Property(property: 'total_moves', type: 'integer', example: 55),
                        new OA\Property(property: 'correct_moves', type: 'integer', example: 40),
                    ], type: 'object'),
                ],
                type: 'object',
            ))],
        ),
    )]
    public function list(): JsonResponse
    {
        $sessions = $this->queryBus->ask(new GetOpenSessionsQuery(
            requesterUserUuid: $this->getSecurityUser()->getUuid(),
        ));

        return new JsonResponse(['data' => $sessions]);
    }

    #[Route('/api/sessions/mine', name: 'user_session_mine', methods: ['GET'])]
    #[OA\Parameter(name: 'mode', description: 'Filter by mode', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['individual', 'shared']))]
    #[OA\Parameter(name: 'status', description: 'Filter by status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['open', 'closed']))]
    #[OA\Response(
        response: 200,
        description: 'Sessions started or played by the current user (newest first)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                properties: [
                    new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'puzzle_uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'visibility', type: 'string', enum: ['public'], example: 'public'),
                    new OA\Property(property: 'mode', type: 'string', enum: ['individual', 'shared'], example: 'shared'),
                    new OA\Property(property: 'created_by_user_uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'status', type: 'string', enum: ['open', 'closed'], example: 'open'),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'closed_at', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'total_pieces', type: 'integer', nullable: true, example: 500),
                    new OA\Property(property: 'correct_pieces', type: 'integer', nullable: true, example: 120),
                    new OA\Property(property: 'leader', nullable: true, properties: [
                        new OA\Property(property: 'user_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'username', type: 'string', example: 'player1'),
                        new OA\Property(property: 'correct_count', type: 'integer', example: 40),
                        new OA\Property(property: 'total_moves', type: 'integer', example: 55),
                        new OA\Property(property: 'correct_moves', type: 'integer', example: 40),
                    ], type: 'object'),
                ],
                type: 'object',
            ))],
        ),
    )]
    public function mine(Request $request): JsonResponse
    {
        $mode = $request->query->get('mode') ?: null;
        $status = $request->query->get('status') ?: null;

        $sessions = $this->queryBus->ask(new GetMySessionsQuery(
            requesterUserUuid: $this->getSecurityUser()->getUuid(),
            mode: $mode !== null ? (string) $mode : null,
            status: $status !== null ? (string) $status : null,
        ));

        return new JsonResponse(['data' => $sessions]);
    }

    #[Route('/api/sessions/stats/mine', name: 'user_session_stats_mine', methods: ['GET'])]
    #[OA\Parameter(name: 'puzzle_uuid', description: 'Filter by puzzle', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, example: 1))]
    #[OA\Parameter(name: 'per_page', description: 'Items per page (max 100)', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, example: 20))]
    #[OA\Response(
        response: 200,
        description: 'Aggregate stats (time spent, pieces placed) of the current user\'s own purged sessions, newest first',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'session_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'puzzle_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'created_by_user_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'mode', type: 'string', enum: ['individual', 'shared'], example: 'individual'),
                        new OA\Property(property: 'total_pieces', type: 'integer', example: 500),
                        new OA\Property(property: 'correct_pieces', type: 'integer', example: 500),
                        new OA\Property(property: 'time_spent_seconds', type: 'integer', example: 1830),
                        new OA\Property(property: 'started_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'finished_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'recorded_at', type: 'string', format: 'date-time'),
                    ],
                    type: 'object',
                )),
                new OA\Property(property: 'meta', properties: [
                    new OA\Property(property: 'page', type: 'integer', example: 1),
                    new OA\Property(property: 'per_page', type: 'integer', example: 20),
                    new OA\Property(property: 'total', type: 'integer', example: 42),
                    new OA\Property(property: 'pages', type: 'integer', example: 3),
                ], type: 'object'),
            ],
        ),
    )]
    public function statsMine(Request $request): JsonResponse
    {
        $puzzleUuid = $request->query->get('puzzle_uuid') ?: null;
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(100, (int) $request->query->get('per_page', 20)));

        $result = $this->queryBus->ask(new GetMySessionStatsQuery(
            requesterUserUuid: $this->getSecurityUser()->getUuid(),
            puzzleUuid: $puzzleUuid !== null ? (string) $puzzleUuid : null,
            page: $page,
            perPage: $perPage,
        ));

        return new JsonResponse($result);
    }

    #[Route('/api/sessions/{sessionUuid}', name: 'user_session_show', methods: ['GET'])]
    #[OA\Parameter(name: 'sessionUuid', description: 'Session UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(
        response: 200,
        description: 'Session details',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
                new OA\Property(property: 'puzzle_uuid', type: 'string', format: 'uuid'),
                new OA\Property(property: 'visibility', type: 'string', enum: ['public'], example: 'public'),
                new OA\Property(property: 'mode', type: 'string', enum: ['individual', 'shared'], example: 'individual'),
                new OA\Property(property: 'created_by_user_uuid', type: 'string', format: 'uuid'),
                new OA\Property(property: 'status', type: 'string', enum: ['open', 'closed'], example: 'open'),
                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'closed_at', type: 'string', format: 'date-time', nullable: true),
                new OA\Property(property: 'total_pieces', type: 'integer', nullable: true),
                new OA\Property(property: 'correct_pieces', type: 'integer', nullable: true),
                new OA\Property(property: 'leader', type: 'object', nullable: true),
            ], type: 'object')],
        ),
    )]
    #[OA\Response(
        response: 404,
        description: 'Session not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Puzzle session not found.')]),
    )]
    public function show(string $sessionUuid): JsonResponse
    {
        $session = $this->queryBus->ask(new GetSessionQuery($sessionUuid));

        return new JsonResponse(['data' => $session]);
    }

    #[Route('/api/sessions/{sessionUuid}/board', name: 'user_session_board', methods: ['GET'])]
    #[OA\Parameter(name: 'sessionUuid', description: 'Session UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(
        response: 200,
        description: 'Current board state (latest position of each piece)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                properties: [
                    new OA\Property(property: 'piece_index', type: 'integer', example: 12),
                    new OA\Property(property: 'to_x', type: 'integer', example: 3),
                    new OA\Property(property: 'to_y', type: 'integer', example: 5),
                    new OA\Property(property: 'correct', type: 'boolean', example: true),
                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                ],
                type: 'object',
            ))],
        ),
    )]
    #[OA\Response(
        response: 404,
        description: 'Session not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Puzzle session not found.')]),
    )]
    public function board(string $sessionUuid): JsonResponse
    {
        $board = $this->queryBus->ask(new GetBoardStateQuery($sessionUuid));

        return new JsonResponse(['data' => $board]);
    }

    #[Route('/api/sessions/{sessionUuid}/moves', name: 'user_session_moves', methods: ['GET'])]
    #[OA\Parameter(name: 'sessionUuid', description: 'Session UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(
        response: 200,
        description: 'Append-only log of piece moves for this session',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                properties: [
                    new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'session_uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'user_uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'piece_index', type: 'integer', example: 12),
                    new OA\Property(property: 'to_x', type: 'integer', example: 3),
                    new OA\Property(property: 'to_y', type: 'integer', example: 5),
                    new OA\Property(property: 'correct', type: 'boolean', example: true),
                    new OA\Property(property: 'seq', type: 'integer', example: 42),
                    new OA\Property(property: 'moved_at', type: 'string', format: 'date-time'),
                ],
                type: 'object',
            ))],
        ),
    )]
    #[OA\Response(
        response: 404,
        description: 'Session not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Puzzle session not found.')]),
    )]
    public function moves(string $sessionUuid): JsonResponse
    {
        $moves = $this->queryBus->ask(new GetSessionMovesQuery($sessionUuid));

        return new JsonResponse(['data' => $moves]);
    }

    #[Route('/api/sessions/{sessionUuid}/contributions', name: 'user_session_contributions', methods: ['GET'])]
    #[OA\Parameter(name: 'sessionUuid', description: 'Session UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(
        response: 200,
        description: 'Per-user contribution ranking for this session',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'total', type: 'integer', example: 500, description: 'Total pieces in the puzzle'),
                new OA\Property(property: 'correct', type: 'integer', example: 120, description: 'Pieces currently correctly placed'),
                new OA\Property(property: 'leader', nullable: true, properties: [
                    new OA\Property(property: 'user_uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'username', type: 'string', example: 'player1'),
                    new OA\Property(property: 'correct_count', type: 'integer', example: 40),
                    new OA\Property(property: 'total_moves', type: 'integer', example: 55),
                    new OA\Property(property: 'correct_moves', type: 'integer', example: 40),
                ], type: 'object'),
                new OA\Property(property: 'contributions', type: 'array', description: 'Ranked, highest first', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'user_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'username', type: 'string', example: 'player1'),
                        new OA\Property(property: 'correct_count', type: 'integer', example: 40),
                        new OA\Property(property: 'total_moves', type: 'integer', example: 55),
                        new OA\Property(property: 'correct_moves', type: 'integer', example: 40),
                    ],
                    type: 'object',
                )),
            ], type: 'object')],
        ),
    )]
    #[OA\Response(
        response: 404,
        description: 'Session not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Puzzle session not found.')]),
    )]
    public function contributions(string $sessionUuid): JsonResponse
    {
        $contributions = $this->queryBus->ask(new GetSessionContributionsQuery($sessionUuid));

        return new JsonResponse(['data' => $contributions]);
    }

    #[Route('/api/sessions/{sessionUuid}/close', name: 'user_session_close', methods: ['POST'])]
    #[OA\Parameter(name: 'sessionUuid', description: 'Session UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(
        response: 200,
        description: 'Session closed',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'message', type: 'string', example: 'Session closed.')]),
    )]
    #[OA\Response(
        response: 404,
        description: 'Session not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Puzzle session not found.')]),
    )]
    #[OA\Response(
        response: 403,
        description: 'Requester is not the session owner',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Only the session owner can perform this action.')]),
    )]
    public function close(string $sessionUuid): JsonResponse
    {
        $this->commandBus->dispatch(new ClosePuzzleSessionCommand(
            sessionUuid: $sessionUuid,
            requestedByUserUuid: $this->getSecurityUser()->getUuid(),
        ));

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
