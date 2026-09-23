<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\WebSocket;

use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

/**
 * Validates the lexik JWT presented during the WebSocket handshake and resolves
 * the matching application user. The WebSocket process runs outside the Symfony
 * firewall, so the token is verified manually with the same key/encoder as the API.
 */
final class ConnectionAuthenticator
{
    public function __construct(
        private readonly JWTEncoderInterface $jwtEncoder,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function authenticate(?string $token): AuthenticatedClient
    {
        if ($token === null || $token === '') {
            throw new AuthenticationFailedException('Missing authentication token.');
        }

        try {
            $payload = $this->jwtEncoder->decode($token);
        } catch (\Throwable $e) {
            throw new AuthenticationFailedException('Invalid or expired token.', previous: $e);
        }

        // user_id_claim is configured as "email" in lexik_jwt_authentication.yaml.
        $email = $payload['email'] ?? $payload['username'] ?? null;

        if (!is_string($email) || $email === '') {
            throw new AuthenticationFailedException('Token does not contain a user identifier.');
        }

        $user = $this->userRepository->findByEmail(new Email($email));

        if ($user === null) {
            throw new AuthenticationFailedException('User not found.');
        }

        return new AuthenticatedClient(
            userUuid: $user->getUuid()->value(),
            email: $email,
            username: $user->getUsername()->value(),
        );
    }
}
