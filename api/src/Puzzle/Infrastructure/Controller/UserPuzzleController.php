<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Controller;

use App\Puzzle\Application\Query\GetPuzzle\GetPuzzleQuery;
use App\Puzzle\Application\Query\GetPuzzles\GetPuzzlesQuery;
use App\Puzzle\Application\Storage\PuzzleStorageInterface;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/puzzles')]
#[OA\Tag(name: 'Puzzles')]
#[Security(name: 'Bearer')]
final class UserPuzzleController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly PuzzleStorageInterface $puzzleStorage,
    ) {}

    #[Route('', name: 'user_puzzles_list', methods: ['GET'])]
    #[OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, example: 1))]
    #[OA\Parameter(name: 'per_page', description: 'Items per page (max 100)', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, example: 20))]
    #[OA\Response(
        response: 200,
        description: 'Paginated list of puzzles',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'uuid', type: 'string', format: 'uuid', example: '9f1c8e2a-1b2c-4d3e-9f0a-123456789abc'),
                        new OA\Property(property: 'original_name', type: 'string', example: 'sunset.jpg'),
                        new OA\Property(property: 'mime_type', type: 'string', example: 'image/jpeg'),
                        new OA\Property(property: 'size', type: 'integer', example: 204800),
                        new OA\Property(property: 'difficulty', type: 'integer', nullable: true, example: 3),
                        new OA\Property(property: 'total_pieces', type: 'integer', nullable: true, example: 500),
                        new OA\Property(property: 'pieces_per_fragment', type: 'integer', nullable: true, example: 50),
                        new OA\Property(property: 'pieces_x', type: 'integer', nullable: true, example: 25),
                        new OA\Property(property: 'pieces_y', type: 'integer', nullable: true, example: 20),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-01-01T12:00:00+00:00'),
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
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(100, (int) $request->query->get('per_page', 20)));
        $result = $this->queryBus->ask(new GetPuzzlesQuery(
            page: $page,
            perPage: $perPage,
        ));

        return new JsonResponse($result);
    }

    #[Route('/{uuid}', name: 'user_puzzles_show', methods: ['GET'])]
    #[OA\Parameter(name: 'uuid', description: 'Puzzle UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(
        response: 200,
        description: 'Puzzle details',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'uuid', type: 'string', format: 'uuid', example: '9f1c8e2a-1b2c-4d3e-9f0a-123456789abc'),
                new OA\Property(property: 'original_name', type: 'string', example: 'sunset.jpg'),
                new OA\Property(property: 'mime_type', type: 'string', example: 'image/jpeg'),
                new OA\Property(property: 'size', type: 'integer', example: 204800),
                new OA\Property(property: 'difficulty', type: 'integer', nullable: true, example: 3),
                new OA\Property(property: 'total_pieces', type: 'integer', nullable: true, example: 500),
                new OA\Property(property: 'pieces_per_fragment', type: 'integer', nullable: true, example: 50),
                new OA\Property(property: 'pieces_x', type: 'integer', nullable: true, example: 25),
                new OA\Property(property: 'pieces_y', type: 'integer', nullable: true, example: 20),
                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-01-01T12:00:00+00:00'),
            ], type: 'object')],
        ),
    )]
    #[OA\Response(
        response: 404,
        description: 'Puzzle not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Puzzle not found.')]),
    )]
    public function show(string $uuid): JsonResponse
    {
        $puzzle = $this->queryBus->ask(new GetPuzzleQuery($uuid));

        return new JsonResponse(['data' => $puzzle]);
    }

    #[Route('/{uuid}/image', name: 'user_puzzles_image', methods: ['GET'])]
    #[OA\Parameter(name: 'uuid', description: 'Puzzle UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'The puzzle image binary content')]
    #[OA\Response(
        response: 404,
        description: 'Puzzle or image file not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Image file not found.')]),
    )]
    public function image(string $uuid): Response
    {
        $puzzle = $this->queryBus->ask(new GetPuzzleQuery($uuid));

        $path = $this->puzzleStorage->getPath($puzzle->storedFilename ?? '');

        if (!file_exists($path)) {
            return new JsonResponse(['error' => 'Image file not found.'], Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $puzzle->originalName,
        );
        $response->headers->set('Content-Type', $puzzle->mimeType);

        return $response;
    }
}
