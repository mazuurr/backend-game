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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserProgressController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {}

    #[Route('/api/puzzles/{uuid}/progress', name: 'user_puzzle_progress_record', methods: ['POST'])]
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

        try {
            $this->commandBus->dispatch(new RecordProgressCommand(
                userUuid: $this->getSecurityUser()->getUuid(),
                puzzleUuid: $uuid,
                currentFragment: $currentFragment,
                piecesPlaced: $piecesPlaced,
                timeSpentSeconds: $timeSpentSeconds,
                completed: (bool) ($data['completed'] ?? false),
            ));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Progress recorded.']);
    }

    #[Route('/api/puzzles/{uuid}/progress', name: 'user_puzzle_progress_get', methods: ['GET'])]
    public function getForPuzzle(string $uuid): JsonResponse
    {
        try {
            $this->queryBus->ask(new GetPuzzleQuery($uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

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
