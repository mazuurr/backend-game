<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/**
 * Base for domain exceptions that carry their own HTTP status, so every
 * controller can map them the same way (see
 * App\Shared\Infrastructure\EventListener\ExceptionListener) instead of each
 * call site hand-picking — and sometimes getting wrong, or guessing from the
 * exception's message text — a status of its own.
 */
abstract class DomainException extends \DomainException
{
    protected const HTTP_BAD_REQUEST = 400;
    protected const HTTP_FORBIDDEN = 403;
    protected const HTTP_NOT_FOUND = 404;
    protected const HTTP_CONFLICT = 409;

    public function __construct(string $message, private readonly int $httpStatus)
    {
        parent::__construct($message);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
