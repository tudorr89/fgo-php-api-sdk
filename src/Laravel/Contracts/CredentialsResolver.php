<?php

declare(strict_types=1);

namespace FgoApi\Laravel\Contracts;

/**
 * Resolves FGO credentials at runtime — e.g. from the database, the current
 * tenant, the authenticated user, or a config repository.
 *
 * Implementations must return an array with at minimum:
 *   - cod_unic    (string)
 *   - private_key (string)
 *   - platform_url (string)
 * Optionally:
 *   - environment (string|FgoApi\Enums\Environment) — "test" / "production" / custom URL
 *   - timeout (int) seconds
 *
 * @phpstan-type FgoCredentials array{
 *     cod_unic: string,
 *     private_key: string,
 *     platform_url: string,
 *     environment?: string|\FgoApi\Enums\Environment,
 *     timeout?: int,
 * }
 */
interface CredentialsResolver
{
    /**
     * @param  string|null $key Optional discriminator (tenant id, user id, account slug, …).
     *                          When null, return the "current" credentials (current tenant / user).
     * @return array<string, mixed>
     */
    public function resolve(?string $key = null): array;
}
