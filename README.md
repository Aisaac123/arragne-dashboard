# Customize Dashboard Widget

A Filament plugin that allows users to customize their dashboard widgets with drag & drop functionality.

## Installation

```bash
composer require shreejan/customize-dashboard-widget
```

## Setup

### Quick Setup (Recommended)

Run the setup command which will automatically:
- Run migrations
- Publish the Dashboard stub
- Update your `AdminPanelProvider.php`

```bash
php artisan publish:dashboard
```

That's it! The package is now ready to use.

### Manual Setup

If you prefer to set up manually:

#### 1. Publish and Run Migrations

```bash
php artisan vendor:publish --tag=customize-dashboard-widget-migrations
php artisan vendor:publish --tag=customize-dashboard-widget-dashboard
php artisan migrate
```

#### 2. Update Your Dashboard Page

Update your `app/Filament/Pages/Dashboard.php` to use the customizable dashboard:

```php
<?php

namespace App\Filament\Pages;

use Shreejan\CustomizeDashboardWidget\Traits\HasCustomizableDashboard;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    use HasCustomizableDashboard;

    protected string $view = 'customize-dashboard-widget::dashboard';

    public function mount(): void
    {
        parent::mount();
        $this->mountHasCustomizableDashboard();
    }
}
```

#### 3. Update AdminPanelProvider

Make sure your `app/Providers/Filament/AdminPanelProvider.php` uses the correct Dashboard class:

```php
use App\Filament\Pages\Dashboard; // Instead of Filament\Pages\Dashboard
```

#### 4. (Optional) Publish Configuration

```bash
php artisan vendor:publish --tag=customize-dashboard-widget-config
```

## Usage

Once installed, users will see a **"Customize My Dashboard"** button on their dashboard. They can:

- Drag and drop widgets to reorder them
- Show/hide widgets using checkboxes
- Save their preferences (stored per user)

## Configuration

Edit `config/customize-dashboard-widget.php` to customize:

- **Grid columns**: Default number of columns for the dashboard grid
- **User model**: Customize the user model if needed
- **Permission checks**: Add custom permission logic for widgets
- **Customize Dashboard Button**: 
  - `customize_dashboard_title`: The title text for the customize dashboard button (default: 'Customize My Dashboard')
  - `customize_dashboard_button_color`: The color of the customize dashboard button. Colors can be added in `AdminPanelProvider.php` -> `colors` array (default: 'primary')

## Requirements

- PHP ^8.4
- Filament ^4.0
- Laravel ^12.0

## License

MIT

