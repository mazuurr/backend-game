<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventListener;

use App\Puzzle\Domain\Exception\PuzzleSessionClosedException;
use App\Shared\Infrastructure\EventListener\ExceptionListener;
use App\User\Domain\Exception\EmailAlreadyExistsException;
use App\User\Domain\Exception\UserNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[CoversClass(ExceptionListener::class)]
final class ExceptionListenerTest extends TestCase
{
    #[DataProvider('exceptionCases')]
    public function testMapsExceptionToStatusCode(\Throwable $exception, int $expectedStatus): void
    {
        $event = $this->eventFor($exception);

        (new ExceptionListener())->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame($expectedStatus, $response->getStatusCode());
    }

    public static function exceptionCases(): array
    {
        return [
            // Each DomainException subclass carries its own status now, instead
            // of every one of them flattening to a blanket 400.
            'user not found' => [new UserNotFoundException(), Response::HTTP_NOT_FOUND],
            'email taken' => [new EmailAlreadyExistsException(), Response::HTTP_CONFLICT],
            'session closed' => [new PuzzleSessionClosedException(), Response::HTTP_CONFLICT],
            // A plain \DomainException/\InvalidArgumentException not routed
            // through our typed hierarchy still falls back to 400.
            'invalid argument' => [new \InvalidArgumentException('Invalid UUID'), Response::HTTP_BAD_REQUEST],
            'bare domain exception' => [new \DomainException('untyped'), Response::HTTP_BAD_REQUEST],
            'http 404' => [new NotFoundHttpException('Nie znaleziono'), Response::HTTP_NOT_FOUND],
            'http 403' => [new AccessDeniedHttpException(), Response::HTTP_FORBIDDEN],
            'unexpected' => [new \RuntimeException('boom'), Response::HTTP_INTERNAL_SERVER_ERROR],
        ];
    }

    public function testResponseBodyCarriesTheExceptionMessage(): void
    {
        $event = $this->eventFor(new UserNotFoundException());

        (new ExceptionListener())->onKernelException($event);

        self::assertSame(
            ['error' => 'User not found.'],
            json_decode((string) $event->getResponse()?->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    private function eventFor(\Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }
}
