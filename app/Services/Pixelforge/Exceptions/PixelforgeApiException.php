<?php

namespace App\Services\Pixelforge\Exceptions;

use Throwable;

class PixelforgeApiException extends PixelforgeException
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(
        string $message,
        protected string $errorCode = 'unknown_error',
        protected int $statusCode = 500,
        protected string $errorType = 'api_error',
        protected ?string $param = null,
        protected array $errors = [],
        protected ?int $retryAfter = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getParam(): ?string
    {
        return $this->param;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public function isAuthenticationError(): bool
    {
        return $this->statusCode === 401 || $this->errorCode === 'invalid_api_key';
    }

    public function isRateLimited(): bool
    {
        return $this->statusCode === 429;
    }

    public function isOverloaded(): bool
    {
        return $this->statusCode === 503 || $this->errorCode === 'overloaded';
    }

    public function isNotFound(): bool
    {
        return $this->statusCode === 404 || $this->errorCode === 'not_found';
    }

    public function isValidationFailed(): bool
    {
        return $this->statusCode === 422 || $this->errorCode === 'validation_failed';
    }

    public function isConcurrencyLimit(): bool
    {
        return $this->errorCode === 'concurrency_limit_exceeded';
    }

    public function isDailyLimit(): bool
    {
        return $this->errorCode === 'daily_limit_exceeded';
    }

    public function isContentPolicyViolation(): bool
    {
        return $this->errorCode === 'content_policy_violation';
    }

    public function isIdempotencyReused(): bool
    {
        return $this->statusCode === 409 || $this->errorCode === 'idempotency_key_reused';
    }
}
