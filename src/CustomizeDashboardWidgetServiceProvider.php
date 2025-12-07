<?php

namespace Shreejan\CustomizeDashboardWidget;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;
use Shreejan\CustomizeDashboardWidget\Console\Commands\InstallCustomizableDashboard;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CustomizeDashboardWidgetServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('customize-dashboard-widget')
            ->hasConfigFile()
            ->hasViews('customize-dashboard-widget')
            ->hasMigrations([
                '2025_01_09_000000_create_user_widget_preferences_table'
            ])
            ->hasConfigFile('customize-dashboard-widget')
            ->hasCommands([
                InstallCustomizableDashboard::class,
            ]);
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        // Publish Dashboard stub 
        $this->publishes([
            __DIR__.'/../stubs/Dashboard.php.stub' => app_path('Filament/Pages/Dashboard.php'),
        ], 'customize-dashboard-widget-dashboard');

        
        // Register Sortable.js asset
        FilamentAsset::register([
            Js::make('sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js')
                ->loadedOnRequest(),
            Css::make('dashboard-customization', __DIR__.'/../resources/dist/css/dashboard-customization.css')
        ], package: 'shreejan/customize-dashboard-widget');
    }
}