<?php

namespace Almarwa\WhatsappGateway;

use Almarwa\WhatsappGateway\Manager\WhatsappGatewayManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class WhatsappGatewayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/whatsapp-gateway.php',
            'whatsapp-gateway'
        );

        $this->app->singleton('whatsapp-gateway', function ($app) {
            return new WhatsappGatewayManager(
                $app,
                $app['config']->get('whatsapp-gateway') ?: []
            );
        });

        $this->app->alias('whatsapp-gateway', WhatsappGatewayManager::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'whatsapp-gateway');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'whatsapp-gateway');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../resources/lang');

        if (config('whatsapp-gateway.storage.enabled', true)) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        // Register the package's class-based components under the
        // `whatsapp-gateway::` namespace so they can be used as
        // <x-whatsapp-gateway::promo-banner /> and
        // <x-whatsapp-gateway::announcement-banner />.
        // (Laravel 8's loadViewComponentsAs joins the prefix with "-" instead
        //  of "::", so we use componentNamespace here for the right syntax.)
        Blade::componentNamespace(
            'Almarwa\\WhatsappGateway\\View\\Components',
            'whatsapp-gateway'
        );

        $this->registerRoutes();

        $this->publishes([
            __DIR__ . '/../config/whatsapp-gateway.php' => config_path('whatsapp-gateway.php'),
        ], 'whatsapp-gateway-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/whatsapp-gateway'),
        ], 'whatsapp-gateway-views');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'whatsapp-gateway-migrations');
    }

    protected function registerRoutes(): void
    {
        $cfg = config('whatsapp-gateway.routes', []);
        if (! ($cfg['enabled'] ?? true)) {
            return;
        }

        $prefix     = trim($cfg['prefix'] ?? 'whatsapp', '/');
        $middleware = $cfg['middleware'] ?? ['web'];

        // If Mcamara/LaravelLocalization is installed, mount the routes
        // under the active locale so /ar/whatsapp and /en/whatsapp both work.
        if (class_exists(LaravelLocalization::class)) {
            $locale = LaravelLocalization::setLocale();
            if ($locale) {
                $prefix = $locale . '/' . $prefix;
            }
            $middleware = array_values(array_unique(array_merge(
                $middleware,
                ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
            )));
        }

        $group = [
            'prefix'     => $prefix,
            'as'         => $cfg['name'] ?? 'whatsapp-gateway.',
            'middleware' => $middleware,
        ];

        Route::group($group, function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }
}
