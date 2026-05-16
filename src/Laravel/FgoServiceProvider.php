<?php

declare(strict_types=1);

namespace FgoApi\Laravel;

use FgoApi\Client;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class FgoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/fgo.php', 'fgo');

        $this->app->singleton(FgoManager::class, static fn (Container $app): FgoManager => new FgoManager($app));

        $this->app->bind(Client::class, static fn (Container $app): Client => $app->make(FgoManager::class)->default());

        $this->app->alias(FgoManager::class, 'fgo');
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
        return [Client::class, FgoManager::class, 'fgo'];
    }
}
