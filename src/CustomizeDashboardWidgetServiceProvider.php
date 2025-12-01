<?php

namespace Shreejan\CustomizeDashboardWidget;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;

class CustomizeDashboardWidgetServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config file
        $this->mergeConfigFrom(
            __DIR__.'/../config/customize-dashboard-widget.php',
            'customize-dashboard-widget'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Sortable.js asset
        FilamentAsset::register([
            Js::make('sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js')
                ->loadedOnRequest(),
        ], package: 'shreejan/customize-dashboard-widget');

        // Publish config
        $this->publishes([
            __DIR__.'/../config/customize-dashboard-widget.php' => config_path('customize-dashboard-widget.php'),
        ], 'customize-dashboard-widget-config');

        // Publish Dashboard stub (optional - for new projects)
        $this->publishes([
            __DIR__.'/../stubs/Dashboard.php.stub' => app_path('Filament/Pages/Dashboard.php'),
        ], 'customize-dashboard-widget-dashboard');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/customize-dashboard-widget'),
        ], 'customize-dashboard-widget-views');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'customize-dashboard-widget-migrations');

        // Load views from package
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'customize-dashboard-widget');
    }
}