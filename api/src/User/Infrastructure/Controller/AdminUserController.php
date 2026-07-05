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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/users')]
final class AdminUserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {}

    #[Route('', name: 'admin_users_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(100, (int) $request->query->get('per_page', 20)));
        $search = $request->query->get('search') ?: null;

        $activeRaw = $request->query->get('active');
        $active = $activeRaw !== null ? filter_var($activeRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $premiumRaw = $request->query->get('premium');
        $premium = $premiumRaw !== null ? filter_var($premiumRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $result = $this->queryBus->ask(new GetUsersQuery(
            page: $page,
            perPage: $perPage,
            active: $active,
            premium: $premium,
            search: $search,
        ));

        return new JsonResponse($result);
    }

    #[Route('/{uuid}', name: 'admin_users_show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $user = $this->queryBus->ask(new GetUserQuery($uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $user]);
    }

    #[Route('', name: 'admin_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->commandBus->dispatch(new CreateUserAdminCommand(
                username: $data['username'] ?? throw new \InvalidArgumentException('Username is required.'),
                email: $data['email'] ?? throw new \InvalidArgumentException('Email is required.'),
                password: $data['password'] ?? throw new \InvalidArgumentException('Password is required.'),
                premium: $data['premium'] ?? false,
                active: $data['active'] ?? true,
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(['message' => 'User created.'], Response::HTTP_CREATED);
    }

    #[Route('/{uuid}', name: 'admin_users_update', methods: ['PUT', 'PATCH'])]
    public function update(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->commandBus->dispatch(new UpdateUserAdminCommand(
                uuid: $uuid,
                username: $data['username'] ?? null,
                email: $data['email'] ?? null,
                password: $data['password'] ?? null,
                premium: $data['premium'] ?? null,
                active: $data['active'] ?? null,
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'User updated.']);
    }

    #[Route('/{uuid}', name: 'admin_users_delete', methods: ['DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new DeleteUserCommand($uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{uuid}/activate', name: 'admin_users_activate', methods: ['POST'])]
    public function activate(string $uuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new ActivateUserCommand($uuid));
        } catch (\DomainException $e) {
            $status = str_contains($e->getMessage(), 'not found') ? Response::HTTP_NOT_FOUND : Response::HTTP_CONFLICT;

            return new JsonResponse(['error' => $e->getMessage()], $status);
        }

        return new JsonResponse(['message' => 'User activated.']);
    }

    #[Route('/{uuid}/send-password-reset', name: 'admin_users_send_password_reset', methods: ['POST'])]
    public function sendPasswordReset(string $uuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new AdminRequestPasswordResetCommand(userUuid: $uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Password reset email sent.']);
    }

    #[Route('/{uuid}/reset-password', name: 'admin_users_reset_password', methods: ['POST'])]
    public function resetPassword(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->commandBus->dispatch(new UpdateUserAdminCommand(
                uuid: $uuid,
                username: null,
                email: null,
                password: $data['password'] ?? throw new \InvalidArgumentException('Password is required.'),
                premium: null,
                active: null,
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Password reset.']);
    }

    #[Route('/{uuid}/deactivate', name: 'admin_users_deactivate', methods: ['POST'])]
    public function deactivate(string $uuid): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new DeactivateUserCommand($uuid));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['message' => 'User deactivated.']);
    }
}
