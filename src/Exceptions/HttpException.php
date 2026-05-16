<?php

declare(strict_types=1);

namespace FgoApi\Exceptions;

class HttpException extends FgoApiException
{
    public function __construct(
        string $message = '',
        private readonly int $statusCode = 0,
        private readonly ?string $responseBody = null,
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}
