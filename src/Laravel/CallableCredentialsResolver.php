<?php

declare(strict_types=1);

namespace FgoApi\Laravel;

use FgoApi\Laravel\Contracts\CredentialsResolver;

/**
 * Adapts a plain callable into a CredentialsResolver — used when `fgo.resolver`
 * is set to a closure or `[Class::class, 'method']` pair.
 */
final class CallableCredentialsResolver implements CredentialsResolver
{
    /** @var callable */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function resolve(?string $key = null): array
    {
        $result = ($this->callback)($key);

        if (!\is_array($result)) {
            throw new \UnexpectedValueException(
                'FGO credentials resolver callable must return an array.'
            );
        }

        return $result;
    }
}
