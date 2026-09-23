<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Controller;

use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\User\Application\Command\RequestPasswordReset\RequestPasswordResetCommand;
use App\User\Application\Command\ResetPasswordByToken\ResetPasswordByTokenCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/password-reset')]
#[OA\Tag(name: 'Auth')]
final class PasswordResetController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    #[Route('/request', name: 'password_reset_request', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Email of the account to request a password reset for',
        content: new OA\JsonContent(
            type: 'object',
            required: ['email'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Reset link sent if the email exists (always returned, does not reveal existence of the account)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'message', type: 'string', example: 'If the email exists, a reset link has been sent.')],
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'Missing email',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'Email is required.')],
        ),
    )]
    public function request(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->commandBus->dispatch(new RequestPasswordResetCommand(
            email: $data['email'] ?? throw new \InvalidArgumentException('Email is required.'),
        ));

        // Zawsze zwracamy 200 — nie ujawniamy czy email istnieje w systemie
        return new JsonResponse(['message' => 'If the email exists, a reset link has been sent.']);
    }

    #[Route('/confirm', name: 'password_reset_confirm', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Reset token and new password',
        content: new OA\JsonContent(
            type: 'object',
            required: ['token', 'password'],
            properties: [
                new OA\Property(property: 'token', type: 'string', example: 'a1b2c3d4e5f6'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'newSecret123'),
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Password has been reset',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'message', type: 'string', example: 'Password has been reset.')],
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'Missing token/password, or invalid/expired token',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'Invalid or expired password reset token.')],
        ),
    )]
    public function confirm(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->commandBus->dispatch(new ResetPasswordByTokenCommand(
            token: $data['token'] ?? throw new \InvalidArgumentException('Token is required.'),
            newPassword: $data['password'] ?? throw new \InvalidArgumentException('Password is required.'),
        ));

        return new JsonResponse(['message' => 'Password has been reset.']);
    }
}
