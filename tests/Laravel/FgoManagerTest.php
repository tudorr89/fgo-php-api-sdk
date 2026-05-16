<?php

declare(strict_types=1);

namespace FgoApi\Tests\Laravel;

use FgoApi\Client;
use FgoApi\Enums\Environment;
use FgoApi\Laravel\CallableCredentialsResolver;
use FgoApi\Laravel\Contracts\CredentialsResolver;
use FgoApi\Laravel\FgoManager;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FgoManagerTest extends TestCase
{
    /**
     * @param array<string, mixed> $fgoConfig
     */
    private function manager(array $fgoConfig): FgoManager
    {
        $container = new Container();
        $container->instance('config', new Repository(['fgo' => $fgoConfig]));

        return new FgoManager($container);
    }

    public function test_default_builds_client_from_static_config(): void
    {
        $client = $this->manager([
            'cod_unic' => 'CUI',
            'private_key' => 'KEY',
            'platform_url' => 'https://x.test',
            'environment' => 'production',
            'timeout' => 30,
        ])->default();

        self::assertInstanceOf(Client::class, $client);
        self::assertSame('CUI', $client->getCodUnic());
        self::assertSame('https://x.test', $client->getPlatformUrl());
    }

    public function test_default_throws_when_credentials_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->manager(['cod_unic' => '', 'private_key' => ''])->default();
    }

    public function test_for_uses_resolver_with_key_and_caches(): void
    {
        $calls = [];
        $resolver = new CallableCredentialsResolver(function (?string $key) use (&$calls): array {
            $calls[] = $key;
            return [
                'cod_unic' => "cui-{$key}",
                'private_key' => "key-{$key}",
                'platform_url' => 'https://x.test',
            ];
        });

        $manager = $this->manager(['resolver' => $resolver]);

        $a = $manager->for('tenant-1');
        $b = $manager->for('tenant-1');
        $c = $manager->for('tenant-2');

        self::assertSame($a, $b, 'same key should be memoised');
        self::assertNotSame($a, $c);
        self::assertSame(['tenant-1', 'tenant-2'], $calls);
        self::assertSame('cui-tenant-1', $a->getCodUnic());
    }

    public function test_for_throws_when_no_resolver_set(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->manager(['cod_unic' => 'a', 'private_key' => 'b'])->for('x');
    }

    public function test_make_builds_ad_hoc_clients_without_caching(): void
    {
        $manager = $this->manager([]);

        $a = $manager->make([
            'cod_unic' => 'A', 'private_key' => 'K', 'platform_url' => 'https://x',
        ]);
        $b = $manager->make([
            'cod_unic' => 'A', 'private_key' => 'K', 'platform_url' => 'https://x',
        ]);

        self::assertNotSame($a, $b);
        self::assertSame('A', $a->getCodUnic());
    }

    public function test_forget_clears_cache(): void
    {
        $resolver = new CallableCredentialsResolver(static fn (?string $k): array => [
            'cod_unic' => "cui-{$k}",
            'private_key' => 'k',
            'platform_url' => 'https://x',
        ]);
        $manager = $this->manager(['resolver' => $resolver]);

        $a = $manager->for('t1');
        $manager->forget('t1');
        $b = $manager->for('t1');

        self::assertNotSame($a, $b);
    }

    public function test_call_forwards_to_default_client(): void
    {
        $manager = $this->manager([
            'cod_unic' => 'CUI', 'private_key' => 'K', 'platform_url' => 'https://x',
        ]);

        self::assertSame('CUI', $manager->getCodUnic());
    }

    public function test_resolver_class_string_is_resolved_from_container(): void
    {
        $container = new Container();
        $container->instance('config', new Repository(['fgo' => ['resolver' => InlineResolver::class]]));
        $manager = new FgoManager($container);

        $client = $manager->default();

        self::assertSame('inline-cui', $client->getCodUnic());
    }

    public function test_environment_string_is_normalised_to_enum(): void
    {
        $client = $this->manager([
            'cod_unic' => 'a', 'private_key' => 'b', 'platform_url' => 'https://x',
            'environment' => 'PRODUCTION',
        ])->default();

        // No public accessor for env; verify by hitting base_uri indirectly via Guzzle config.
        $base = (string) $client->getHttpClient()->getConfig('base_uri');
        self::assertStringContainsString(Environment::Production->value, $base);
    }
}

final class InlineResolver implements CredentialsResolver
{
    public function resolve(?string $key = null): array
    {
        return [
            'cod_unic' => 'inline-cui',
            'private_key' => 'inline-key',
            'platform_url' => 'https://inline.test',
        ];
    }
}
