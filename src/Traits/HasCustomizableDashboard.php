<?php

namespace Shreejan\CustomizeDashboardWidget\Traits;

use Shreejan\CustomizeDashboardWidget\Models\UserWidgetPreference;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

trait HasCustomizableDashboard
{
    /**
     * @var array<array{name: string, sort: int, title: string, visible: bool}>
     */
    public array $permittedWidgets = [];

    /**
     * @var array<array{name: string, sort: int, title: string, visible: bool}>
     */
    public array $visibleWidgets = [];

    /**
     * @var array<array{name: string, sort: int, title: string, visible: bool}>
     */
    public array $currentWidgets = [];

    /**
     * Initialize widget arrays on mount.
     */
    public function mountHasCustomizableDashboard(): void
    {
        $this->visibleWidgets = $this->getSortedVisibleWidgets();
        $this->permittedWidgets = $this->getPermittedWidgets();
        $this->currentWidgets = $this->visibleWidgets;
    }

    /**
     * Revert changes to the last saved state.
     */
    public function revertChanges(): void
    {
        if ($this->visibleWidgets === $this->currentWidgets) {
            return;
        }

        $this->currentWidgets = $this->visibleWidgets;
        $this->permittedWidgets = $this->getPermittedWidgets();
    }

    /**
     * Update user widget preferences.
     *
     * @param  array<string>  $sortedWidgets
     */
    public function updateUserWidgetPreferences(array $sortedWidgets): void
    {
        $this->updateVisibleWidgets($sortedWidgets);
        $this->hideRemovedWidgets($sortedWidgets);
        $this->refreshWidgetData();
        $this->notifySuccess();
    }

    /**
     * Update current widgets array after drag & drop.
     *
     * @param  array<string>  $sortedWidgets
     */
    public function updateCurrentWidgets(array $sortedWidgets): void
    {
        $this->currentWidgets = array_filter(array_map($this->widgetDataMapper(true), $sortedWidgets));
    }

    /**
     * Add a widget to the dashboard.
     */
    public function addWidget(string $widgetName): void
    {
        $index = array_search($widgetName, array_column($this->permittedWidgets, 'name'));

        if ($index === false) {
            return;
        }

        $widget = $this->permittedWidgets[$index];
        $widget['visible'] = true;

        $index = array_search($widgetName, array_column($this->currentWidgets, 'name'));

        $index !== false ? $this->currentWidgets[$index]['visible'] = true : $this->currentWidgets[] = $widget;
    }

    /**
     * Remove a widget from the dashboard.
     */
    public function removeWidget(string $widgetName): void
    {
        $index = array_search($widgetName, array_column($this->currentWidgets, 'name'));

        if ($index !== false) {
            $this->currentWidgets[$index]['visible'] = false;
        }
    }

    /**
     * Get default grid columns configuration.
     */
    public function getColumns(): int|array
    {
        return config('customize-dashboard-widget.default_grid_columns', [
            'md' => 2,
            'xl' => 12,
        ]);
    }

    /**
     * Get all permitted widgets (user has permission to see).
     *
     * @return array<array{name: string, sort: int, title: string, visible: bool}>
     */
    private function getPermittedWidgets(): array
    {
        $permissionCheck = config('customize-dashboard-widget.permission_check', fn (string $widgetClass) => true);

        return collect($this->getWidgets())
            ->filter(function ($widget) use ($permissionCheck) {
                if (! class_exists($widget)) {
                    return false;
                }

                $resolvedWidget = resolve($widget);
                
                // Check if widget has hasPermission method (FilamentShield)
                if (method_exists($resolvedWidget, 'hasPermission')) {
                    return $resolvedWidget->hasPermission();
                }

                // Use custom permission check from config
                return $permissionCheck($widget);
            })
            ->map($this->widgetDataMapper())
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Get sorted visible widgets based on user preferences.
     *
     * @return array<array{name: string, sort: int, title: string, visible: bool}>
     */
    private function getSortedVisibleWidgets(): array
    {
        $userId = $this->getUserId();
        
        if (! $userId) {
            return [];
        }

        // Get all user preferences (not just visible ones) to check if user has any preferences
        $allPreferences = UserWidgetPreference::where('user_id', $userId)
            ->pluck('show_widget', 'widget_name')
            ->toArray();

        // Get visible widget preferences for ordering
        $visiblePreferences = UserWidgetPreference::where('user_id', $userId)
            ->where('show_widget', true)
            ->orderBy('order')
            ->pluck('order', 'widget_name')
            ->toArray();

        // If user has no preferences at all, show all widgets by default
        $hasPreferences = ! empty($allPreferences);

        return collect($this->getWidgets())
            ->filter(function ($widgetClass) use ($allPreferences, $hasPreferences) {
                // If user has preferences, only include widgets that are explicitly set to visible
                if ($hasPreferences) {
                    return isset($allPreferences[$widgetClass]) && $allPreferences[$widgetClass] === true;
                }
                // If no preferences, show all widgets
                return true;
            })
            ->sortBy(function ($widgetClass) use ($visiblePreferences) {
                if (isset($visiblePreferences[$widgetClass])) {
                    return $visiblePreferences[$widgetClass];
                }
                
                $resolvedWidget = resolve($widgetClass);
                if (method_exists($resolvedWidget, 'getSort')) {
                    return $resolvedWidget->getSort() ?? 999;
                }
                
                return 999;
            })
            ->map(function ($widget) use ($hasPreferences) {
                return $this->widgetDataMapper(! $hasPreferences)($widget);
            })
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Map widget class to data array.
     */
    private function widgetDataMapper(?bool $visible = false): Closure
    {
        return function ($widget) use ($visible) {
            if (! class_exists($widget)) {
                return null;
            }

            $resolvedWidget = resolve($widget);
            $userId = $this->getUserId();

            // Check if widget should be visible
            // $visible parameter indicates: true = show all (new user), false = check database (user has preferences)
            $isVisible = $visible;
            if (! $isVisible && $userId) {
                $preference = UserWidgetPreference::where('user_id', $userId)
                    ->where('widget_name', get_class($resolvedWidget))
                    ->first();
                
                // If user has preferences, only show if explicitly set to true
                // If no preference exists and user has preferences, default to false (hidden)
                $isVisible = $preference?->show_widget ?? false;
            }

            // Get widget title/heading safely
            $title = class_basename($widget);
            if (method_exists($resolvedWidget, 'getHeading')) {
                $title = $resolvedWidget->getHeading() ?? $title;
            } elseif (method_exists($resolvedWidget, 'getLabel')) {
                $title = $resolvedWidget->getLabel() ?? $title;
            } elseif (property_exists($resolvedWidget, 'heading')) {
                $title = $resolvedWidget->heading ?? $title;
            } elseif (property_exists($resolvedWidget, 'label')) {
                $title = $resolvedWidget->label ?? $title;
            }

            return [
                'name' => get_class($resolvedWidget),
                'sort' => method_exists($resolvedWidget, 'getSort') ? ($resolvedWidget->getSort() ?? 0) : 0,
                'title' => $title,
                'visible' => $isVisible,
            ];
        };
    }

    /**
     * Update visible widgets order.
     *
     * @param  array<string>  $sortedWidgets
     */
    private function updateVisibleWidgets(array $sortedWidgets): void
    {
        $userId = $this->getUserId();

        if (! $userId) {
            return;
        }

        foreach ($sortedWidgets as $index => $widgetName) {
            UserWidgetPreference::updateOrCreate(
                ['user_id' => $userId, 'widget_name' => $widgetName],
                ['order' => $index + 1, 'show_widget' => true]
            );
        }
    }

    /**
     * Hide widgets that were removed from dashboard.
     *
     * @param  array<string>  $sortedWidgets
     */
    private function hideRemovedWidgets(array $sortedWidgets): void
    {
        $userId = $this->getUserId();

        if (! $userId) {
            return;
        }

        $removedWidgets = array_filter($this->currentWidgets, function ($widget) use ($sortedWidgets) {
            return ! in_array($widget['name'], $sortedWidgets);
        });

        UserWidgetPreference::where('user_id', $userId)
            ->whereIn('widget_name', array_column($removedWidgets, 'name'))
            ->update(['show_widget' => false]);
    }

    /**
     * Refresh widget data after saving.
     */
    private function refreshWidgetData(): void
    {
        $this->visibleWidgets = $this->currentWidgets = $this->getSortedVisibleWidgets();
        $this->permittedWidgets = $this->getPermittedWidgets();
    }

    /**
     * Show success notification.
     */
    private function notifySuccess(): void
    {
        Notification::make()
            ->success()
            ->title('Saved')
            ->send();
    }

    /**
     * Get current user ID from config.
     */
    private function getUserId(): ?int
    {
        $resolver = config('customize-dashboard-widget.user_id_resolver', fn () => Auth::id());

        return $resolver();
    }
}

