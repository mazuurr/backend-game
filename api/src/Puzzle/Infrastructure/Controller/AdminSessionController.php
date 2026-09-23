<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Controller;

use App\Puzzle\Application\Command\ClosePuzzleSession\ClosePuzzleSessionCommand;
use App\Puzzle\Application\Query\GetSession\GetSessionQuery;
use App\Puzzle\Application\Query\GetSessions\GetSessionsQuery;
use App\Puzzle\Application\Query\GetSessionStats\GetSessionStatsQuery;
use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/sessions')]
#[OA\Tag(name: 'Admin - Sessions')]
#[Security(name: 'ApiToken')]
final class AdminSessionController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {}

    #[Route('', name: 'admin_sessions_list', methods: ['GET'])]
    #[OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, example: 1))]
    #[OA\Parameter(name: 'per_page', description: 'Items per page (max 100)', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, example: 20))]
    #[OA\Parameter(name: 'status', description: 'Filter by status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['open', 'closed'], nullable: true))]
    #[OA\Response(
        response: 200,
        description: 'Paginated list of sessions',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(
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
                        new OA\Property(property: 'total_pieces', type: 'integer', nullable: true),
                        new OA\Property(property: 'correct_pieces', type: 'integer', nullable: true),
                        new OA\Property(property: 'leader', type: 'object', nullable: true),
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
    #[OA\Response(
        response: 400,
        description: 'Invalid status filter',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'status must be "open" or "closed".')]),
    )]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(100, (int) $request->query->get('per_page', 20)));

        $status = $request->query->get('status') ?: null;
        if ($status !== null && !in_array($status, [PuzzleSession::STATUS_OPEN, PuzzleSession::STATUS_CLOSED], true)) {
            return new JsonResponse(['error' => 'status must be "open" or "closed".'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->queryBus->ask(new GetSessionsQuery(
            page: $page,
            perPage: $perPage,
            status: $status,
        ));

        return new JsonResponse($result);
    }

    #[Route('/stats', name: 'admin_sessions_stats', methods: ['GET'])]
    #[OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, example: 1))]
    #[OA\Parameter(name: 'per_page', description: 'Items per page (max 100)', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, example: 20))]
    #[OA\Response(
        response: 200,
        description: 'Paginated aggregate stats (time spent, pieces placed) of purged sessions, newest first',
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
    public function stats(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(100, (int) $request->query->get('per_page', 20)));

        $result = $this->queryBus->ask(new GetSessionStatsQuery(
            page: $page,
            perPage: $perPage,
        ));

        return new JsonResponse($result);
    }

    #[Route('/{uuid}', name: 'admin_sessions_show', methods: ['GET'])]
    #[OA\Parameter(name: 'uuid', description: 'Session UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
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
    public function show(string $uuid): JsonResponse
    {
        $session = $this->queryBus->ask(new GetSessionQuery($uuid));

        return new JsonResponse(['data' => $session]);
    }

    #[Route('/{uuid}/close', name: 'admin_sessions_close', methods: ['POST'])]
    #[OA\Parameter(name: 'uuid', description: 'Session UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
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
    public function close(string $uuid): JsonResponse
    {
        $this->commandBus->dispatch(new ClosePuzzleSessionCommand(sessionUuid: $uuid));

        return new JsonResponse(['message' => 'Session closed.']);
    }
}
