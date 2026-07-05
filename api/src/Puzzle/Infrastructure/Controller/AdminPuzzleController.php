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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/puzzles')]
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
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(100, (int) $request->query->get('per_page', 20)));
        $campaignUuid = $request->query->get('campaign_uuid') ?: null;

        $result = $this->queryBus->ask(new GetPuzzlesQuery(
            page: $page,
            perPage: $perPage,
            campaignUuid: $campaignUuid,
        ));

        return new JsonResponse($result);
    }

    #[Route('/{uuid}', name: 'admin_puzzles_show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $puzzle = $this->queryBus->ask(new GetPuzzleQuery($uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $puzzle]);
    }

    #[Route('/{uuid}/image', name: 'admin_puzzles_image', methods: ['GET'])]
    public function image(string $uuid): Response
    {
        try {
            $puzzle = $this->queryBus->ask(new GetPuzzleQuery($uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

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

        try {
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
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Puzzle updated.']);
    }

    #[Route('/{uuid}', name: 'admin_puzzles_delete', methods: ['DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new DeletePuzzleCommand(uuid: $uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{uuid}/progress', name: 'admin_puzzles_progress', methods: ['GET'])]
    public function progress(string $uuid): JsonResponse
    {
        try {
            $progressList = $this->queryBus->ask(new GetPuzzleProgressQuery($uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $progressList]);
    }
}
