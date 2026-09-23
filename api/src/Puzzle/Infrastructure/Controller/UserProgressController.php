<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Controller;

use App\Puzzle\Application\Command\RecordProgress\RecordProgressCommand;
use App\Puzzle\Application\Query\GetMyProgress\GetMyProgressQuery;
use App\Puzzle\Application\Query\GetPuzzle\GetPuzzleQuery;
use App\Puzzle\Application\Query\GetPuzzleProgress\GetPuzzleProgressQuery;
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

#[OA\Tag(name: 'Progress')]
#[ApiDocSecurity(name: 'Bearer')]
final class UserProgressController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {}

    #[Route('/api/puzzles/{uuid}/progress', name: 'user_puzzle_progress_record', methods: ['POST'])]
    #[OA\Parameter(name: 'uuid', description: 'Puzzle UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\RequestBody(
        description: 'Progress update for the current user on this puzzle',
        content: new OA\JsonContent(
            type: 'object',
            required: ['current_fragment', 'pieces_placed', 'time_spent_seconds'],
            properties: [
                new OA\Property(property: 'current_fragment', type: 'integer', example: 1, description: 'Must be >= 1'),
                new OA\Property(property: 'pieces_placed', type: 'integer', example: 20, description: 'Must be >= 0'),
                new OA\Property(property: 'time_spent_seconds', type: 'integer', example: 120, description: 'Must be >= 0'),
                new OA\Property(property: 'completed', type: 'boolean', example: false, nullable: true),
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Progress recorded',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'message', type: 'string', example: 'Progress recorded.')]),
    )]
    #[OA\Response(
        response: 400,
        description: 'Missing or invalid fields',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Fields current_fragment, pieces_placed, time_spent_seconds are required.')]),
    )]
    #[OA\Response(
        response: 404,
        description: 'Puzzle not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Puzzle not found.')]),
    )]
    public function record(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $currentFragment = isset($data['current_fragment']) ? (int) $data['current_fragment'] : null;
        $piecesPlaced = isset($data['pieces_placed']) ? (int) $data['pieces_placed'] : null;
        $timeSpentSeconds = isset($data['time_spent_seconds']) ? (int) $data['time_spent_seconds'] : null;

        if ($currentFragment === null || $piecesPlaced === null || $timeSpentSeconds === null) {
            return new JsonResponse(
                ['error' => 'Fields current_fragment, pieces_placed, time_spent_seconds are required.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        if ($currentFragment < 1) {
            return new JsonResponse(['error' => 'current_fragment must be >= 1.'], Response::HTTP_BAD_REQUEST);
        }

        if ($piecesPlaced < 0 || $timeSpentSeconds < 0) {
            return new JsonResponse(['error' => 'pieces_placed and time_spent_seconds must be >= 0.'], Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(new RecordProgressCommand(
            userUuid: $this->getSecurityUser()->getUuid(),
            puzzleUuid: $uuid,
            currentFragment: $currentFragment,
            piecesPlaced: $piecesPlaced,
            timeSpentSeconds: $timeSpentSeconds,
            completed: (bool) ($data['completed'] ?? false),
        ));

        return new JsonResponse(['message' => 'Progress recorded.']);
    }

    #[Route('/api/puzzles/{uuid}/progress', name: 'user_puzzle_progress_get', methods: ['GET'])]
    #[OA\Parameter(name: 'uuid', description: 'Puzzle UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(
        response: 200,
        description: 'Current user progress on this puzzle (null if none recorded yet)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'data', nullable: true, properties: [
                new OA\Property(property: 'user_uuid', type: 'string', format: 'uuid'),
                new OA\Property(property: 'puzzle_uuid', type: 'string', format: 'uuid'),
                new OA\Property(property: 'current_fragment', type: 'integer', example: 1),
                new OA\Property(property: 'pieces_placed', type: 'integer', example: 20),
                new OA\Property(property: 'pieces_per_fragment', type: 'integer', nullable: true, example: 50),
                new OA\Property(property: 'fragment_percent', type: 'number', format: 'float', nullable: true, example: 40.0),
                new OA\Property(property: 'time_spent_seconds', type: 'integer', example: 120),
                new OA\Property(property: 'completed', type: 'boolean', example: false),
                new OA\Property(property: 'started_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
            ], type: 'object')],
        ),
    )]
    #[OA\Response(
        response: 404,
        description: 'Puzzle not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Puzzle not found.')]),
    )]
    public function getForPuzzle(string $uuid): JsonResponse
    {
        $this->queryBus->ask(new GetPuzzleQuery($uuid));

        $progressList = $this->queryBus->ask(new GetPuzzleProgressQuery($uuid));

        $userUuid = $this->getSecurityUser()->getUuid();
        $myProgress = null;

        foreach ($progressList as $progress) {
            if ($progress->userUuid === $userUuid) {
                $myProgress = $progress;
                break;
            }
        }

        if ($myProgress === null) {
            return new JsonResponse(['data' => null]);
        }

        return new JsonResponse(['data' => $myProgress]);
    }

    #[Route('/api/me/progress', name: 'user_my_progress', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Progress of the current user across all puzzles',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                properties: [
                    new OA\Property(property: 'user_uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'puzzle_uuid', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'current_fragment', type: 'integer', example: 1),
                    new OA\Property(property: 'pieces_placed', type: 'integer', example: 20),
                    new OA\Property(property: 'pieces_per_fragment', type: 'integer', nullable: true, example: 50),
                    new OA\Property(property: 'fragment_percent', type: 'number', format: 'float', nullable: true, example: 40.0),
                    new OA\Property(property: 'time_spent_seconds', type: 'integer', example: 120),
                    new OA\Property(property: 'completed', type: 'boolean', example: false),
                    new OA\Property(property: 'started_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                ],
                type: 'object',
            ))],
        ),
    )]
    public function myProgress(): JsonResponse
    {
        $progress = $this->queryBus->ask(new GetMyProgressQuery(
            userUuid: $this->getSecurityUser()->getUuid(),
        ));

        return new JsonResponse(['data' => $progress]);
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
