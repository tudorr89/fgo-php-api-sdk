<?php

declare(strict_types=1);

namespace FgoApi\Laravel;

use FgoApi\Client;
use FgoApi\Enums\Environment;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class FgoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/fgo.php', 'fgo');

        $this->app->singleton(Client::class, static function (Container $app): Client {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('fgo', []);

            $environment = $config['environment'] ?? 'test';
            if (\is_string($environment)) {
                $environment = match (\strtolower($environment)) {
                    'production', 'prod', 'live' => Environment::Production,
                    'test', 'testing', 'uat', 'sandbox' => Environment::Test,
                    default => $environment,
                };
            }

            return new Client(
                codUnic: (string) ($config['cod_unic'] ?? ''),
                privateKey: (string) ($config['private_key'] ?? ''),
                platformUrl: (string) ($config['platform_url'] ?? ''),
                environment: $environment,
                timeout: (int) ($config['timeout'] ?? 20),
            );
        });

        $this->app->alias(Client::class, 'fgo');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/fgo.php' => $this->app->configPath('fgo.php'),
            ], 'fgo-config');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [Client::class, 'fgo'];
    }
}
