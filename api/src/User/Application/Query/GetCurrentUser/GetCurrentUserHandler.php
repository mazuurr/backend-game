<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetCurrentUser;

use App\User\Application\DTO\UserDTO;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetCurrentUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(GetCurrentUserQuery $query): UserDTO
    {
        $user = $this->userRepository->findByUuid(new UserId($query->uuid));

        if ($user === null) {
            throw new UserNotFoundException();
        }

        return UserDTO::fromEntity($user);
    }
}
