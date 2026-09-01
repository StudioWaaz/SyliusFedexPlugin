<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Api\Exception;

use RuntimeException;

class FedexApiException extends RuntimeException
{
    /**
     * @param array<string, mixed> $errors
     */
    public function __construct(
        string $message,
        private int $statusCode = 0,
        private array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
