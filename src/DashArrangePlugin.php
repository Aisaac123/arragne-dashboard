<?php

namespace Shreejan\DashArrange;

use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * DashArrange Plugin.
 *
 * Filament plugin for customizable dashboard widgets.
 */
class DashArrangePlugin implements Plugin
{
    /**
     * Get the plugin ID.
     */
    public function getId(): string
    {
        return 'dash-arrange';
    }

    /**
     * Create a new plugin instance.
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Get the plugin instance from Filament.
     */
    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * Register the plugin.
     */
    public function register(Panel $panel): void
    {
        //
    }

    /**
     * Boot the plugin.
     */
    public function boot(Panel $panel): void
    {
        //
    }
}

