<?php

namespace App\Filament\Pages;

use Shreejan\DashArrange\Traits\HasDashArrange;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    use HasDashArrange;

    protected string $view = 'dash-arrange::dashboard';

    public function mount(): void
    {
        // Initialize DashArrange functionality
        $this->mountHasDashArrange();
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }
}