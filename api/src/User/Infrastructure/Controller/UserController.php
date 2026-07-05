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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/me')]
final class UserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {}

    #[Route('', name: 'user_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $securityUser = $this->getSecurityUser();

        $user = $this->queryBus->ask(new GetCurrentUserQuery($securityUser->getUuid()));

        return new JsonResponse(['data' => $user]);
    }

    #[Route('', name: 'user_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request): JsonResponse
    {
        $securityUser = $this->getSecurityUser();
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->commandBus->dispatch(new UpdateUserCommand(
                uuid: $securityUser->getUuid(),
                username: $data['username'] ?? null,
                email: $data['email'] ?? null,
                password: $data['password'] ?? null,
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['message' => 'Profile updated.']);
    }

    #[Route('/change-password', name: 'user_change_password', methods: ['POST'])]
    public function changePassword(Request $request): JsonResponse
    {
        $securityUser = $this->getSecurityUser();
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->commandBus->dispatch(new ChangePasswordCommand(
                userUuid: $securityUser->getUuid(),
                currentPassword: $data['current_password'] ?? throw new \InvalidArgumentException('current_password is required.'),
                newPassword: $data['new_password'] ?? throw new \InvalidArgumentException('new_password is required.'),
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(['message' => 'Password changed.']);
    }

    #[Route('/deactivate', name: 'user_deactivate', methods: ['POST'])]
    public function deactivate(): JsonResponse
    {
        $securityUser = $this->getSecurityUser();

        try {
            $this->commandBus->dispatch(new DeactivateUserCommand($securityUser->getUuid()));
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

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
