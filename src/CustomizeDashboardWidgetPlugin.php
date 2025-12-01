<?php

namespace Shreejan\CustomizeDashboardWidget;

use Filament\Contracts\Plugin;
use Filament\Panel;

class CustomizeDashboardWidgetPlugin implements Plugin
{
    public function getId(): string
    {
        return 'customize-dashboard-widget';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }
}