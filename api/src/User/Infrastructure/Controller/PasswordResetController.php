<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Controller;

use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\User\Application\Command\RequestPasswordReset\RequestPasswordResetCommand;
use App\User\Application\Command\ResetPasswordByToken\ResetPasswordByTokenCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/password-reset')]
final class PasswordResetController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    #[Route('/request', name: 'password_reset_request', methods: ['POST'])]
    public function request(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->commandBus->dispatch(new RequestPasswordResetCommand(
                email: $data['email'] ?? throw new \InvalidArgumentException('Email is required.'),
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        // Zawsze zwracamy 200 — nie ujawniamy czy email istnieje w systemie
        return new JsonResponse(['message' => 'If the email exists, a reset link has been sent.']);
    }

    #[Route('/confirm', name: 'password_reset_confirm', methods: ['POST'])]
    public function confirm(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->commandBus->dispatch(new ResetPasswordByTokenCommand(
                token: $data['token'] ?? throw new \InvalidArgumentException('Token is required.'),
                newPassword: $data['password'] ?? throw new \InvalidArgumentException('Password is required.'),
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(['message' => 'Password has been reset.']);
    }
}
