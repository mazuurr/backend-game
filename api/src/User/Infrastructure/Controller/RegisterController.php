<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Controller;

use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\User\Application\Command\CreateUser\CreateUserCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if ($email === null || $password === null) {
            return new JsonResponse(
                ['error' => 'Email and password are required.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $this->commandBus->dispatch(new CreateUserCommand($email, $password));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(
            ['message' => 'User registered. Check your email to activate your account.'],
            Response::HTTP_CREATED,
        );
    }
}
