<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $statusCode = match (true) {
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            $exception instanceof \DomainException => Response::HTTP_BAD_REQUEST,
            $exception instanceof \InvalidArgumentException => Response::HTTP_BAD_REQUEST,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };

        $response = new JsonResponse([
            'error' => $exception->getMessage(),
        ], $statusCode);

        $event->setResponse($response);
    }
}
