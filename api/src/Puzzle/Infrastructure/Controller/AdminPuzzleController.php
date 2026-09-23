<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Controller;

use App\Puzzle\Application\Command\DeletePuzzle\DeletePuzzleCommand;
use App\Puzzle\Application\Command\UpdatePuzzle\UpdatePuzzleCommand;
use App\Puzzle\Application\Command\UploadPuzzle\UploadPuzzleCommand;
use App\Puzzle\Application\Query\GetPuzzle\GetPuzzleQuery;
use App\Puzzle\Application\Query\GetPuzzleProgress\GetPuzzleProgressQuery;
use App\Puzzle\Application\Query\GetPuzzles\GetPuzzlesQuery;
use App\Puzzle\Application\Storage\PuzzleStorageInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/puzzles')]
#[OA\Tag(name: 'Admin - Puzzles')]
#[Security(name: 'ApiToken')]
final class AdminPuzzleController
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ];

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly PuzzleStorageInterface $puzzleStorage,
    ) {}

    #[Route('', name: 'admin_puzzles_list', methods: ['GET'])]
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

    #[Route('/{uuid}', name: 'admin_puzzles_show', methods: ['GET'])]
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

    #[Route('/{uuid}/image', name: 'admin_puzzles_image', methods: ['GET'])]
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

        $path = $this->puzzleStorage->getPath($puzzle->storedFilename);

        if (!file_exists($path)) {
            return new JsonResponse(['error' => 'Image file not found.'], Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $puzzle->originalName);
        $response->headers->set('Content-Type', $puzzle->mimeType);

        return $response;
    }

    #[Route('', name: 'admin_puzzles_upload', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Multipart form upload of a puzzle image',
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                type: 'object',
                required: ['image'],
                properties: [
                    new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Image file (jpeg, png, gif, webp, svg+xml)'),
                    new OA\Property(property: 'difficulty', type: 'integer', nullable: true, example: 3),
                    new OA\Property(property: 'total_pieces', type: 'integer', nullable: true, example: 500),
                    new OA\Property(property: 'pieces_per_fragment', type: 'integer', nullable: true, example: 50),
                    new OA\Property(property: 'pieces_x', type: 'integer', nullable: true, example: 25),
                    new OA\Property(property: 'pieces_y', type: 'integer', nullable: true, example: 20),
                ],
            ),
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'Puzzle uploaded',
        content: new OA\JsonContent(type: 'object', properties: [
            new OA\Property(property: 'uuid', type: 'string', format: 'uuid', example: '9f1c8e2a-1b2c-4d3e-9f0a-123456789abc'),
            new OA\Property(property: 'message', type: 'string', example: 'Puzzle uploaded.'),
        ]),
    )]
    #[OA\Response(
        response: 400,
        description: 'Missing image field',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Field "image" is required.')]),
    )]
    #[OA\Response(
        response: 422,
        description: 'Invalid file type',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Invalid file type. Allowed: image/jpeg, image/png, image/gif, image/webp, image/svg+xml.')]),
    )]
    #[OA\Response(
        response: 500,
        description: 'Storage failure',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Unable to store the uploaded file.')]),
    )]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('image');

        if ($file === null) {
            return new JsonResponse(['error' => 'Field "image" is required.'], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            return new JsonResponse(
                ['error' => sprintf('Invalid file type. Allowed: %s.', implode(', ', self::ALLOWED_MIME_TYPES))],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $uuid = PuzzleId::generate()->value();

        $difficultyRaw = $request->request->get('difficulty');
        $difficulty = $difficultyRaw !== null ? (int) $difficultyRaw : null;

        $totalPiecesRaw = $request->request->get('total_pieces');
        $totalPieces = $totalPiecesRaw !== null ? (int) $totalPiecesRaw : null;

        $piecesPerFragmentRaw = $request->request->get('pieces_per_fragment');
        $piecesPerFragment = $piecesPerFragmentRaw !== null ? (int) $piecesPerFragmentRaw : null;

        $piecesXRaw = $request->request->get('pieces_x');
        $piecesX = $piecesXRaw !== null ? (int) $piecesXRaw : null;

        $piecesYRaw = $request->request->get('pieces_y');
        $piecesY = $piecesYRaw !== null ? (int) $piecesYRaw : null;

        try {
            $this->commandBus->dispatch(new UploadPuzzleCommand(
                uuid: $uuid,
                originalName: $file->getClientOriginalName(),
                tmpPath: $file->getPathname(),
                mimeType: $file->getMimeType(),
                size: $file->getSize(),
                difficulty: $difficulty,
                totalPieces: $totalPieces,
                piecesPerFragment: $piecesPerFragment,
                piecesX: $piecesX,
                piecesY: $piecesY,
            ));
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['uuid' => $uuid, 'message' => 'Puzzle uploaded.'], Response::HTTP_CREATED);
    }

    #[Route('/{uuid}', name: 'admin_puzzles_update', methods: ['PATCH'])]
    #[OA\Parameter(name: 'uuid', description: 'Puzzle UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\RequestBody(
        description: 'At least one field is required',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'difficulty', type: 'integer', nullable: true, example: 3),
                new OA\Property(property: 'total_pieces', type: 'integer', nullable: true, example: 500),
                new OA\Property(property: 'pieces_per_fragment', type: 'integer', nullable: true, example: 50),
                new OA\Property(property: 'pieces_x', type: 'integer', nullable: true, example: 25),
                new OA\Property(property: 'pieces_y', type: 'integer', nullable: true, example: 20),
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Puzzle updated',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'message', type: 'string', example: 'Puzzle updated.')]),
    )]
    #[OA\Response(
        response: 400,
        description: 'No field provided',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'At least one field (difficulty, total_pieces, pieces_per_fragment, pieces_x, pieces_y) is required.')]),
    )]
    #[OA\Response(
        response: 404,
        description: 'Puzzle not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Puzzle not found.')]),
    )]
    public function update(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $hasDifficulty = array_key_exists('difficulty', $data);
        $hasTotalPieces = array_key_exists('total_pieces', $data);
        $hasPiecesPerFragment = array_key_exists('pieces_per_fragment', $data);
        $hasPiecesX = array_key_exists('pieces_x', $data);
        $hasPiecesY = array_key_exists('pieces_y', $data);

        if (!$hasDifficulty && !$hasTotalPieces && !$hasPiecesPerFragment && !$hasPiecesX && !$hasPiecesY) {
            return new JsonResponse(['error' => 'At least one field (difficulty, total_pieces, pieces_per_fragment, pieces_x, pieces_y) is required.'], Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(new UpdatePuzzleCommand(
            uuid: $uuid,
            hasDifficulty: $hasDifficulty,
            difficulty: $hasDifficulty && $data['difficulty'] !== null ? (int) $data['difficulty'] : null,
            hasTotalPieces: $hasTotalPieces,
            totalPieces: $hasTotalPieces && $data['total_pieces'] !== null ? (int) $data['total_pieces'] : null,
            hasPiecesPerFragment: $hasPiecesPerFragment,
            piecesPerFragment: $hasPiecesPerFragment && $data['pieces_per_fragment'] !== null ? (int) $data['pieces_per_fragment'] : null,
            hasPiecesX: $hasPiecesX,
            piecesX: $hasPiecesX && $data['pieces_x'] !== null ? (int) $data['pieces_x'] : null,
            hasPiecesY: $hasPiecesY,
            piecesY: $hasPiecesY && $data['pieces_y'] !== null ? (int) $data['pieces_y'] : null,
        ));

        return new JsonResponse(['message' => 'Puzzle updated.']);
    }

    #[Route('/{uuid}', name: 'admin_puzzles_delete', methods: ['DELETE'])]
    #[OA\Parameter(name: 'uuid', description: 'Puzzle UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 204, description: 'Puzzle deleted')]
    #[OA\Response(
        response: 404,
        description: 'Puzzle not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Puzzle not found.')]),
    )]
    public function delete(string $uuid): JsonResponse
    {
        $this->commandBus->dispatch(new DeletePuzzleCommand(uuid: $uuid));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{uuid}/progress', name: 'admin_puzzles_progress', methods: ['GET'])]
    #[OA\Parameter(name: 'uuid', description: 'Puzzle UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(
        response: 200,
        description: 'Progress of all users on this puzzle',
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
    #[OA\Response(
        response: 404,
        description: 'Puzzle not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string', example: 'Puzzle not found.')]),
    )]
    public function progress(string $uuid): JsonResponse
    {
        $progressList = $this->queryBus->ask(new GetPuzzleProgressQuery($uuid));

        return new JsonResponse(['data' => $progressList]);
    }
}
