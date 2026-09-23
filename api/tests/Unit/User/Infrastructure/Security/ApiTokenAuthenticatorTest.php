<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Security;

use App\User\Infrastructure\Security\ApiAdminUser;
use App\User\Infrastructure\Security\ApiTokenAuthenticator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

#[CoversClass(ApiTokenAuthenticator::class)]
final class ApiTokenAuthenticatorTest extends TestCase
{
    private const TOKEN = 'sekretny-token-admina';

    private ApiTokenAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->authenticator = new ApiTokenAuthenticator(self::TOKEN);
    }

    public function testSupportsOnlyRequestsCarryingTheHeader(): void
    {
        self::assertTrue($this->authenticator->supports($this->requestWithToken(self::TOKEN)));
        self::assertFalse($this->authenticator->supports(new Request()));
    }

    public function testAuthenticatesWithValidToken(): void
    {
        $passport = $this->authenticator->authenticate($this->requestWithToken(self::TOKEN));

        self::assertInstanceOf(ApiAdminUser::class, $passport->getUser());
    }

    public function testRejectsInvalidToken(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid API token.');

        $this->authenticator->authenticate($this->requestWithToken('zly-token'));
    }

    public function testRejectsEmptyToken(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('No API token provided.');

        $this->authenticator->authenticate($this->requestWithToken(''));
    }

    public function testRejectsTokenThatIsOnlyAPrefix(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);

        $this->authenticator->authenticate($this->requestWithToken(substr(self::TOKEN, 0, 5)));
    }

    public function testFailureReturns401Json(): void
    {
        $response = $this->authenticator->onAuthenticationFailure(
            new Request(),
            new CustomUserMessageAuthenticationException('Invalid API token.'),
        );

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Invalid API token.'],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    private function requestWithToken(string $token): Request
    {
        $request = new Request();
        $request->headers->set('X-API-TOKEN', $token);

        return $request;
    }
}
