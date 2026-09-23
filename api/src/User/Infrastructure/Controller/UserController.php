<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Controller;

use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use App\User\Application\Command\ChangePassword\ChangePasswordCommand;
use App\User\Application\Command\DeactivateUser\DeactivateUserCommand;
use App\User\Application\Command\UpdateUser\UpdateUserCommand;
use App\User\Application\Query\GetCurrentUser\GetCurrentUserQuery;
use App\User\Infrastructure\Security\SecurityUser;
use Nelmio\ApiDocBundle\Attribute\Security as ApiDocSecurity;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/me')]
#[OA\Tag(name: 'Me')]
#[ApiDocSecurity(name: 'Bearer')]
final class UserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {}

    #[Route('', name: 'user_me', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Current authenticated user',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'uuid', type: 'string', example: '9f8b6c2e-1234-4a56-9abc-1234567890ab'),
                    new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'active', type: 'boolean', example: true),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-01-15T10:30:00+00:00'),
                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true, example: null),
                ]),
            ],
        ),
    )]
    public function me(): JsonResponse
    {
        $securityUser = $this->getSecurityUser();

        $user = $this->queryBus->ask(new GetCurrentUserQuery($securityUser->getUuid()));

        return new JsonResponse(['data' => $user]);
    }

    #[Route('', name: 'user_update', methods: ['PUT', 'PATCH'])]
    #[OA\RequestBody(
        description: 'Fields to update on the current user profile (all optional)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'username', type: 'string', nullable: true, example: 'johndoe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'user@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', nullable: true, example: 'newSecret123'),
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Profile updated',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'message', type: 'string', example: 'Profile updated.')],
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid data',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'Invalid email format.')],
        ),
    )]
    public function update(Request $request): JsonResponse
    {
        $securityUser = $this->getSecurityUser();
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->commandBus->dispatch(new UpdateUserCommand(
            uuid: $securityUser->getUuid(),
            username: $data['username'] ?? null,
            email: $data['email'] ?? null,
            password: $data['password'] ?? null,
        ));

        return new JsonResponse(['message' => 'Profile updated.']);
    }

    #[Route('/change-password', name: 'user_change_password', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Current and new password',
        content: new OA\JsonContent(
            type: 'object',
            required: ['current_password', 'new_password'],
            properties: [
                new OA\Property(property: 'current_password', type: 'string', format: 'password', example: 'oldSecret123'),
                new OA\Property(property: 'new_password', type: 'string', format: 'password', example: 'newSecret123'),
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Password changed',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'message', type: 'string', example: 'Password changed.')],
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'Missing fields, or current password is incorrect',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'Current password is incorrect.')],
        ),
    )]
    public function changePassword(Request $request): JsonResponse
    {
        $securityUser = $this->getSecurityUser();
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->commandBus->dispatch(new ChangePasswordCommand(
            userUuid: $securityUser->getUuid(),
            currentPassword: $data['current_password'] ?? throw new \InvalidArgumentException('current_password is required.'),
            newPassword: $data['new_password'] ?? throw new \InvalidArgumentException('new_password is required.'),
        ));

        return new JsonResponse(['message' => 'Password changed.']);
    }

    #[Route('/deactivate', name: 'user_deactivate', methods: ['POST'])]
    #[OA\Response(
        response: 200,
        description: 'Account deactivated',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'message', type: 'string', example: 'Account deactivated.')],
        ),
    )]
    #[OA\Response(
        response: 409,
        description: 'Account is already inactive',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'User is already inactive.')],
        ),
    )]
    public function deactivate(): JsonResponse
    {
        $securityUser = $this->getSecurityUser();

        $this->commandBus->dispatch(new DeactivateUserCommand($securityUser->getUuid()));

        return new JsonResponse(['message' => 'Account deactivated.']);
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
