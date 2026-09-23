<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Controller;

use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use App\User\Application\Command\ActivateUser\ActivateUserCommand;
use App\User\Application\Command\AdminRequestPasswordReset\AdminRequestPasswordResetCommand;
use App\User\Application\Command\CreateUserAdmin\CreateUserAdminCommand;
use App\User\Application\Command\DeactivateUser\DeactivateUserCommand;
use App\User\Application\Command\DeleteUser\DeleteUserCommand;
use App\User\Application\Command\UpdateUserAdmin\UpdateUserAdminCommand;
use App\User\Application\Query\GetUser\GetUserQuery;
use App\User\Application\Query\GetUsers\GetUsersQuery;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/users')]
#[OA\Tag(name: 'Admin - Users')]
#[Security(name: 'ApiToken')]
final class AdminUserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {}

    #[Route('', name: 'admin_users_list', methods: ['GET'])]
    #[OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, example: 1))]
    #[OA\Parameter(name: 'per_page', description: 'Items per page (max 100)', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, example: 20))]
    #[OA\Parameter(name: 'search', description: 'Search by username or email', in: 'query', required: false, schema: new OA\Schema(type: 'string', nullable: true, example: 'john'))]
    #[OA\Parameter(name: 'active', description: 'Filter by active status', in: 'query', required: false, schema: new OA\Schema(type: 'boolean', nullable: true, example: true))]
    #[OA\Response(
        response: 200,
        description: 'Paginated list of users',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'uuid', type: 'string', example: '9f8b6c2e-1234-4a56-9abc-1234567890ab'),
                        new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                        new OA\Property(property: 'active', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-01-15T10:30:00+00:00'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true, example: null),
                    ],
                )),
                new OA\Property(property: 'meta', type: 'object', properties: [
                    new OA\Property(property: 'page', type: 'integer', example: 1),
                    new OA\Property(property: 'per_page', type: 'integer', example: 20),
                    new OA\Property(property: 'total', type: 'integer', example: 42),
                    new OA\Property(property: 'pages', type: 'integer', example: 3),
                ]),
            ],
        ),
    )]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(100, (int) $request->query->get('per_page', 20)));
        $search = $request->query->get('search') ?: null;

        $activeRaw = $request->query->get('active');
        $active = $activeRaw !== null ? filter_var($activeRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $result = $this->queryBus->ask(new GetUsersQuery(
            page: $page,
            perPage: $perPage,
            active: $active,
            search: $search,
        ));

        return new JsonResponse($result);
    }

    #[Route('/{uuid}', name: 'admin_users_show', methods: ['GET'])]
    #[OA\Parameter(name: 'uuid', description: 'User UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'User details',
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
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'User not found.')],
        ),
    )]
    public function show(string $uuid): JsonResponse
    {
        $user = $this->queryBus->ask(new GetUserQuery($uuid));

        return new JsonResponse(['data' => $user]);
    }

    #[Route('', name: 'admin_users_create', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'New user data',
        content: new OA\JsonContent(
            type: 'object',
            required: ['username', 'email', 'password'],
            properties: [
                new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                new OA\Property(property: 'active', type: 'boolean', example: true),
            ],
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'User created',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'message', type: 'string', example: 'User created.')],
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'Missing or invalid fields',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'Username is required.')],
        ),
    )]
    #[OA\Response(
        response: 409,
        description: 'Email or username already in use',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'A user with this email already exists.')],
        ),
    )]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->commandBus->dispatch(new CreateUserAdminCommand(
            username: $data['username'] ?? throw new \InvalidArgumentException('Username is required.'),
            email: $data['email'] ?? throw new \InvalidArgumentException('Email is required.'),
            password: $data['password'] ?? throw new \InvalidArgumentException('Password is required.'),
            active: $data['active'] ?? true,
        ));

        return new JsonResponse(['message' => 'User created.'], Response::HTTP_CREATED);
    }

    #[Route('/{uuid}', name: 'admin_users_update', methods: ['PUT', 'PATCH'])]
    #[OA\Parameter(name: 'uuid', description: 'User UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        description: 'Fields to update (all optional)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'username', type: 'string', nullable: true, example: 'johndoe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'user@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', nullable: true, example: 'newSecret123'),
                new OA\Property(property: 'active', type: 'boolean', nullable: true, example: true),
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'User updated',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'message', type: 'string', example: 'User updated.')],
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
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'User not found.')],
        ),
    )]
    public function update(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->commandBus->dispatch(new UpdateUserAdminCommand(
            uuid: $uuid,
            username: $data['username'] ?? null,
            email: $data['email'] ?? null,
            password: $data['password'] ?? null,
            active: $data['active'] ?? null,
        ));

        return new JsonResponse(['message' => 'User updated.']);
    }

    #[Route('/{uuid}', name: 'admin_users_delete', methods: ['DELETE'])]
    #[OA\Parameter(name: 'uuid', description: 'User UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 204, description: 'User deleted')]
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'User not found.')],
        ),
    )]
    public function delete(string $uuid): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteUserCommand($uuid));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{uuid}/activate', name: 'admin_users_activate', methods: ['POST'])]
    #[OA\Parameter(name: 'uuid', description: 'User UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'User activated',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'message', type: 'string', example: 'User activated.')],
        ),
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'User not found.')],
        ),
    )]
    #[OA\Response(
        response: 409,
        description: 'User already active',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'User is already active.')],
        ),
    )]
    public function activate(string $uuid): JsonResponse
    {
        $this->commandBus->dispatch(new ActivateUserCommand($uuid));

        return new JsonResponse(['message' => 'User activated.']);
    }

    #[Route('/{uuid}/send-password-reset', name: 'admin_users_send_password_reset', methods: ['POST'])]
    #[OA\Parameter(name: 'uuid', description: 'User UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'Password reset email sent',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'message', type: 'string', example: 'Password reset email sent.')],
        ),
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'User not found.')],
        ),
    )]
    public function sendPasswordReset(string $uuid): JsonResponse
    {
        $this->commandBus->dispatch(new AdminRequestPasswordResetCommand(userUuid: $uuid));

        return new JsonResponse(['message' => 'Password reset email sent.']);
    }

    #[Route('/{uuid}/reset-password', name: 'admin_users_reset_password', methods: ['POST'])]
    #[OA\Parameter(name: 'uuid', description: 'User UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        description: 'New password to set for the user',
        content: new OA\JsonContent(
            type: 'object',
            required: ['password'],
            properties: [
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'newSecret123'),
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Password reset',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'message', type: 'string', example: 'Password reset.')],
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'Missing password',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'Password is required.')],
        ),
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'User not found.')],
        ),
    )]
    public function resetPassword(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->commandBus->dispatch(new UpdateUserAdminCommand(
            uuid: $uuid,
            username: null,
            email: null,
            password: $data['password'] ?? throw new \InvalidArgumentException('Password is required.'),
            active: null,
        ));

        return new JsonResponse(['message' => 'Password reset.']);
    }

    #[Route('/{uuid}/deactivate', name: 'admin_users_deactivate', methods: ['POST'])]
    #[OA\Parameter(name: 'uuid', description: 'User UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'User deactivated',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'message', type: 'string', example: 'User deactivated.')],
        ),
    )]
    #[OA\Response(
        response: 409,
        description: 'User is already inactive',
        content: new OA\JsonContent(
            type: 'object',
            properties: [new OA\Property(property: 'error', type: 'string', example: 'User is already inactive.')],
        ),
    )]
    public function deactivate(string $uuid): JsonResponse
    {
        $this->commandBus->dispatch(new DeactivateUserCommand($uuid));

        return new JsonResponse(['message' => 'User deactivated.']);
    }
}
