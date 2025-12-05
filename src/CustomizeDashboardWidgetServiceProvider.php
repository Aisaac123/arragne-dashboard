<?php

namespace Shreejan\CustomizeDashboardWidget;

use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;
use Shreejan\CustomizeDashboardWidget\Console\Commands\PublishDashboard;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CustomizeDashboardWidgetServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('customize-dashboard-widget')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations()
            ->hasCommands([
                PublishDashboard::class,
            ]);
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        // Publish config
        $this->publishes([
            __DIR__.'/../config/customize-dashboard-widget.php' => config_path('customize-dashboard-widget.php'),
        ], 'customize-dashboard-widget-config');

        // Publish Dashboard stub 
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

        // Register Sortable.js asset
        FilamentAsset::register([
            Js::make('sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js')
                ->loadedOnRequest(),
        ], package: 'shreejan/customize-dashboard-widget');
    }
}