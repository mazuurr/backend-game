<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly string $apiToken,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-API-TOKEN');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->headers->get('X-API-TOKEN');

        if ($token === null || $token === '') {
            throw new CustomUserMessageAuthenticationException('No API token provided.');
        }

        if (!hash_equals($this->apiToken, $token)) {
            throw new CustomUserMessageAuthenticationException('Invalid API token.');
        }

        // Admin nie jest użytkownikiem w bazie — tworzymy syntetyczny identyfikator
        return new SelfValidatingPassport(
            new UserBadge('api_admin', fn () => new ApiAdminUser()),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // pozwól przejść dalej
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => $exception->getMessageKey(),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
