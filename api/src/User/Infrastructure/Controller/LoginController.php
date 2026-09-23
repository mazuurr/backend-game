<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Controller;

use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use App\User\Infrastructure\Security\SecurityUser;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Auth')]
final class LoginController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {}

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'User credentials',
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
        response: 200,
        description: 'Login successful',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'token', type: 'string', example: 'eyJhbGciOiJSUzI1NiIs...'),
                new OA\Property(property: 'uuid', type: 'string', example: '9f8b6c2e-1234-4a56-9abc-1234567890ab'),
            ],
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'Missing credentials or invalid email format',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'Email and password are required.')],
        ),
    )]
    #[OA\Response(
        response: 401,
        description: 'Invalid credentials',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'Invalid credentials.')],
        ),
    )]
    #[OA\Response(
        response: 403,
        description: 'Account is not active',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'Account is not active. Please check your email for activation link.')],
        ),
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $emailRaw = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if ($emailRaw === null || $password === null) {
            return new JsonResponse(
                ['error' => 'Email and password are required.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $email = new Email($emailRaw);
        } catch (\InvalidArgumentException) {
            return new JsonResponse(['error' => 'Invalid email format.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            return new JsonResponse(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->getPassword()->verify($password)) {
            return new JsonResponse(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->isActive()) {
            return new JsonResponse(
                ['error' => 'Account is not active. Please check your email for activation link.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $securityUser = new SecurityUser($user);
        $token = $this->jwtManager->create($securityUser);

        return new JsonResponse([
            'token' => $token,
            'uuid' => $user->getUuid()->value(),
        ]);
    }
}
