<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Controller;

use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\User\Application\Command\CreateUser\CreateUserCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Auth')]
final class RegisterController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'New account credentials',
        content: new OA\JsonContent(
            type: 'object',
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
            ],
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'User registered',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'message', type: 'string', example: 'User registered. Check your email to activate your account.')],
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'Missing fields or invalid data',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'Email and password are required.')],
        ),
    )]
    #[OA\Response(
        response: 409,
        description: 'Email already registered',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'A user with this email already exists.')],
        ),
    )]
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

        $this->commandBus->dispatch(new CreateUserCommand($email, $password));

        return new JsonResponse(
            ['message' => 'User registered. Check your email to activate your account.'],
            Response::HTTP_CREATED,
        );
    }
}
