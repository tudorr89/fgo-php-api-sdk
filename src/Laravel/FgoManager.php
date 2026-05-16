<?php

declare(strict_types=1);

namespace FgoApi\Laravel;

use FgoApi\Client;
use FgoApi\Endpoints\ArticleEndpoint;
use FgoApi\Endpoints\InvoiceEndpoint;
use FgoApi\Endpoints\NomenclatureEndpoint;
use FgoApi\Endpoints\WarehouseEndpoint;
use FgoApi\Enums\Environment;
use FgoApi\Laravel\Contracts\CredentialsResolver;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Resolves and caches FGO clients per credential set.
 *
 * - `default()` — the "main" client, built from config or the registered resolver.
 * - `for($key)` — credentials looked up via the resolver (DB / tenant / user).
 * - `make($credentials)` — ad-hoc credentials, no caching.
 *
 * Unknown methods are forwarded to `default()`, so `$manager->invoices()` works.
 *
 * @method InvoiceEndpoint      invoices()
 * @method ArticleEndpoint      articles()
 * @method NomenclatureEndpoint nomenclatures()
 * @method WarehouseEndpoint    warehouses()
 */
class FgoManager
{
    /** @var array<string, Client> */
    private array $clients = [];

    private ?Client $defaultClient = null;

    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function default(): Client
    {
        return $this->defaultClient ??= $this->resolveDefault();
    }

    /**
     * Build a client for a specific resolver key (tenant id, user id, …).
     * Results are memoised per-key for the lifetime of the request.
     */
    public function for(string $key): Client
    {
        return $this->clients[$key] ??= $this->build($this->resolverOrFail()->resolve($key));
    }

    /**
     * Build a one-off client from arbitrary credentials. Not cached.
     *
     * @param array<string, mixed> $credentials
     */
    public function make(array $credentials): Client
    {
        return $this->build($credentials);
    }

    /**
     * Forget a memoised client (call after rotating credentials).
     */
    public function forget(?string $key = null): void
    {
        if ($key === null) {
            $this->clients = [];
            $this->defaultClient = null;
            return;
        }

        unset($this->clients[$key]);
    }

    /**
     * Forward unknown calls (invoices(), articles(), …) to the default client.
     *
     * @param array<int, mixed> $args
     */
    public function __call(string $method, array $args): mixed
    {
        return $this->default()->{$method}(...$args);
    }

    private function resolveDefault(): Client
    {
        $resolver = $this->resolver();

        if ($resolver !== null) {
            return $this->build($resolver->resolve(null));
        }

        /** @var array<string, mixed> $config */
        $config = $this->container['config']->get('fgo', []);

        return $this->build($config);
    }

    /**
     * @param array<string, mixed> $credentials
     */
    private function build(array $credentials): Client
    {
        $codUnic = (string) ($credentials['cod_unic'] ?? '');
        $privateKey = (string) ($credentials['private_key'] ?? '');
        $platformUrl = (string) ($credentials['platform_url'] ?? '');

        if ($codUnic === '' || $privateKey === '') {
            throw new InvalidArgumentException(
                'FGO credentials are missing: both "cod_unic" and "private_key" are required.'
            );
        }

        $environment = $credentials['environment'] ?? 'test';
        if (\is_string($environment)) {
            $environment = match (\strtolower($environment)) {
                'production', 'prod', 'live' => Environment::Production,
                'test', 'testing', 'uat', 'sandbox' => Environment::Test,
                default => $environment,
            };
        }

        return new Client(
            codUnic: $codUnic,
            privateKey: $privateKey,
            platformUrl: $platformUrl,
            environment: $environment,
            timeout: (int) ($credentials['timeout'] ?? 20),
        );
    }

    private function resolver(): ?CredentialsResolver
    {
        $resolverConfig = $this->container['config']->get('fgo.resolver');

        if ($resolverConfig === null) {
            return null;
        }

        if ($resolverConfig instanceof CredentialsResolver) {
            return $resolverConfig;
        }

        if (\is_string($resolverConfig)) {
            $instance = $this->container->make($resolverConfig);
            if (!$instance instanceof CredentialsResolver) {
                throw new InvalidArgumentException(\sprintf(
                    'fgo.resolver class "%s" must implement %s.',
                    $resolverConfig,
                    CredentialsResolver::class,
                ));
            }
            return $instance;
        }

        if (\is_callable($resolverConfig)) {
            return new CallableCredentialsResolver($resolverConfig);
        }

        throw new InvalidArgumentException(
            'fgo.resolver must be null, a class-string, a callable, or a CredentialsResolver instance.'
        );
    }

    private function resolverOrFail(): CredentialsResolver
    {
        $resolver = $this->resolver();
        if ($resolver === null) {
            throw new InvalidArgumentException(
                'No FGO credentials resolver is configured. Set "resolver" in config/fgo.php to use Fgo::for($key).'
            );
        }
        return $resolver;
    }
}
