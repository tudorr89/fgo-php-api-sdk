<?php

declare(strict_types=1);

namespace FgoApi\Exceptions;

class RateLimitException extends FgoApiException
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        private readonly int $retryAfter = 0,
    ) {
        parent::__construct($message);
    }

    /**
     * Seconds to wait before retrying, as advertised by the API's Retry-After header.
     * Zero if the API did not provide a hint.
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
