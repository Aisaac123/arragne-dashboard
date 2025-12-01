<?php

namespace Shreejan\CustomizeDashboardWidget;

use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;
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
            ->hasMigrations();
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        // Publish Dashboard stub (optional - for new projects)
        $this->publishes([
            __DIR__.'/../stubs/Dashboard.php.stub' => app_path('Filament/Pages/Dashboard.php'),
        ], 'customize-dashboard-widget-dashboard');

        // Register Sortable.js asset
        FilamentAsset::register([
            Js::make('sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js')
                ->loadedOnRequest(),
        ], package: 'shreejan/customize-dashboard-widget');
    }
}