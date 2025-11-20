<?php

namespace Rifa\ApiClient;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

class RifaApiClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/rifa-api-client.php', 'rifa-api-client');

        $this->app->singleton(RifaApiClient::class, function ($app) {
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);
            /** @var HttpFactory $httpFactory */
            $httpFactory = $app->make(HttpFactory::class);

            $settings = $config->get('rifa-api-client');

            return new RifaApiClient(
                $httpFactory,
                (string) ($settings['base_url'] ?? ''),
                (string) ($settings['public_key'] ?? ''),
                (string) ($settings['secret'] ?? ''),
                (int) ($settings['signature_ttl'] ?? 60),
                (array) ($settings['http'] ?? []),
                (array) ($settings['default_headers'] ?? [])
            );
        });

        $this->app->alias(RifaApiClient::class, 'rifa-api');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/rifa-api-client.php' => config_path('rifa-api-client.php'),
        ], 'rifa-api-client-config');
    }
}
