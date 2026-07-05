<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Controller;

use App\Puzzle\Application\Query\GetPuzzle\GetPuzzleQuery;
use App\Puzzle\Application\Query\GetPuzzles\GetPuzzlesQuery;
use App\Puzzle\Application\Storage\PuzzleStorageInterface;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/puzzles')]
final class UserPuzzleController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly PuzzleStorageInterface $puzzleStorage,
    ) {}

    #[Route('', name: 'user_puzzles_list', methods: ['GET'])]
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

    #[Route('/{uuid}', name: 'user_puzzles_show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $puzzle = $this->queryBus->ask(new GetPuzzleQuery($uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $puzzle]);
    }

    #[Route('/{uuid}/image', name: 'user_puzzles_image', methods: ['GET'])]
    public function image(string $uuid): Response
    {
        try {
            $puzzle = $this->queryBus->ask(new GetPuzzleQuery($uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

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
