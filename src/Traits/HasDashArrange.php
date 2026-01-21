<?php

namespace Shreejan\DashArrange\Traits;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\UserWidgetPreference;
use Throwable;

/**
 * Trait for customizable dashboard functionality.
 *
 * Provides drag-and-drop widget arrangement, visibility toggling,
 * and user preference persistence. Works in any Filament page.
 */
trait HasDashArrange
{
    /**
     * Widget data structure type definition.
     *
     * @var array<array{name: string, sort: int, title: string, visible: bool}>
     */
    public array $permittedWidgets = [];

    /**
     * Currently visible widgets based on user preferences.
     *
     * @var array<array{name: string, sort: int, title: string, visible: bool}>
     */
    public array $visibleWidgets = [];

    /**
     * Current state of widgets (may include unsaved changes).
     *
     * @var array<array{name: string, sort: int, title: string, visible: bool}>
     */
    public array $currentWidgets = [];

    /**
     * Default sort order for widgets without explicit ordering.
     */
    private const DEFAULT_SORT_ORDER = 999;

    /**
     * Default column span for widgets.
     */
    private const DEFAULT_COLUMN_SPAN = 1;

    /**
     * Methods to check for widget title/heading.
     */
    private const WIDGET_TITLE_METHODS = ['getHeading', 'getLabel'];

    /**
     * Get the scope for this page (unique identifier).
     * Override this method to customize the scope.
     *
     * @return string
     */
    protected function getWidgetScope(): string
    {
        // Por defecto usa el nombre de la clase de la página
        return static::class;
    }

    /**
     * Get the widget method name to use.
     * Override to use getHeaderWidgets(), getFooterWidgets(), etc.
     *
     * @return string
     */
    protected function getWidgetMethodName(): string
    {
        return 'getWidgets';
    }

    /**
     * Get widgets from the page.
     * Maneja automáticamente si el método existe o no.
     *
     * @return array<string>
     */
    private function getPageWidgets(): array
    {
        $methodName = $this->getWidgetMethodName();

        if (!method_exists($this, $methodName)) {
            return [];
        }

        try {
            $widgets = $this->$methodName();
            return is_array($widgets) ? $widgets : [];
        } catch (Throwable $e) {
            return [];
        }
    }



    /**
     * Initialize widget arrays on mount.
     */
    public function mountHasDashArrange(): void
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
        if ($this->hasUnsavedChanges()) {
            $this->currentWidgets = $this->visibleWidgets;
            $this->permittedWidgets = $this->getPermittedWidgets();
        }
    }

    /**
     * Update user widget preferences.
     *
     * @param  array<string>  $sortedWidgets  Array of widget class names in desired order
     */
    public function updateUserWidgetPreferences(array $sortedWidgets): void
    {
        $userId = $this->getUserId();

        if (! $userId) {
            return;
        }

        $scope = $this->getWidgetScope();

        $this->updateVisibleWidgets($sortedWidgets, $userId, $scope);
        $this->hideRemovedWidgets($sortedWidgets, $userId, $scope);
        $this->refreshWidgetData();
        $this->notifySuccess();
    }

    /**
     * Update current widgets array after drag & drop.
     *
     * @param  array<string>  $sortedWidgets  Array of widget class names in new order
     */
    public function updateCurrentWidgets(array $sortedWidgets): void
    {
        $this->currentWidgets = array_values(
            array_filter(
                array_map($this->widgetDataMapper(true), $sortedWidgets),
                fn ($widget) => $widget !== null
            )
        );
    }

    /**
     * Add a widget to the dashboard.
     *
     * @param  string  $widgetName  Fully qualified widget class name
     */
    public function addWidget(string $widgetName): void
    {
        $widget = $this->findWidgetInArray($widgetName, $this->permittedWidgets);

        if ($widget === null) {
            return;
        }

        $widget['visible'] = true;
        $existingIndex = $this->findWidgetIndex($widgetName, $this->currentWidgets);

        if ($existingIndex !== null) {
            $this->currentWidgets[$existingIndex]['visible'] = true;
        } else {
            $this->currentWidgets[] = $widget;
        }
    }

    /**
     * Remove a widget from the dashboard.
     *
     * @param  string  $widgetName  Fully qualified widget class name
     */
    public function removeWidget(string $widgetName): void
    {
        $index = $this->findWidgetIndex($widgetName, $this->currentWidgets);

        if ($index !== null) {
            $this->currentWidgets[$index]['visible'] = false;
        }
    }

    /**
     * Get default grid columns configuration.
     *
     * Returns 2 columns (Filament's default).
     *
     * @return int|array<int|string, int>
     */
    public function getColumns(): int|array
    {
        return 2;
    }

    /**
     * Get the column span for a widget instance.
     *
     * Handles 'full', numeric values, and responsive arrays.
     * Matches Filament's default behavior for widget column spans.
     *
     * @param  object  $widgetInstance  The widget instance
     * @param  int  $gridColumns  Total number of grid columns
     * @return int  Column span value (1 to $gridColumns)
     */
    public function getWidgetColumnSpan(object $widgetInstance, int $gridColumns): int
    {
        if (! method_exists($widgetInstance, 'getColumnSpan')) {
            return self::DEFAULT_COLUMN_SPAN;
        }

        try {
            $columnSpan = $widgetInstance->getColumnSpan();

            return $this->normalizeColumnSpan($columnSpan, $gridColumns);
        } catch (Throwable $e) {
            return self::DEFAULT_COLUMN_SPAN;
        }
    }

    /**
     * Get all permitted widgets (user has permission to see).
     *
     * @return array<array{name: string, sort: int, title: string, visible: bool}>
     */
    private function getPermittedWidgets(): array
    {
        $permissionCheck = $this->getPermissionCheckClosure();

        return collect($this->getPageWidgets())
            ->filter(fn (string $widgetClass) => $this->isWidgetPermitted($widgetClass, $permissionCheck))
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

        $scope = $this->getWidgetScope();
        $preferences = $this->getUserPreferences($userId, $scope);
        $hasPreferences = ! empty($preferences['all']);

        return collect($this->getPageWidgets())
            ->filter(fn (string $widgetClass) => $this->shouldIncludeWidget($widgetClass, $preferences, $hasPreferences))
            ->sortBy(fn (string $widgetClass) => $this->getWidgetSortOrder($widgetClass, $preferences['visible']))
            ->map($this->widgetDataMapper(! $hasPreferences))
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Map widget class to data array.
     *
     * @param  bool|null  $forceVisible  Force visibility (true = show all, false = check DB)
     * @return Closure
     */
    private function widgetDataMapper(?bool $forceVisible = false): Closure
    {
        return function (string $widgetClass) use ($forceVisible): ?array {
            if (! class_exists($widgetClass)) {
                return null;
            }

            try {
                $resolvedWidget = resolve($widgetClass);
                $isVisible = $this->determineWidgetVisibility($resolvedWidget, $forceVisible);
                $title = $this->getWidgetTitle($resolvedWidget, $widgetClass);
                $sort = $this->getWidgetSort($resolvedWidget);

                return [
                    'name' => get_class($resolvedWidget),
                    'sort' => $sort,
                    'title' => $title,
                    'visible' => $isVisible,
                ];
            } catch (Throwable $e) {
                return null;
            }
        };
    }

    /**
     * Update visible widgets order in database.
     *
     * @param  array<string>  $sortedWidgets  Widget class names in desired order
     * @param  int  $userId  User ID
     * @param  string  $scope  Page scope
     */
    private function updateVisibleWidgets(array $sortedWidgets, int $userId, string $scope): void
    {
        foreach ($sortedWidgets as $index => $widgetName) {
            UserWidgetPreference::updateOrCreate(
                [
                    'user_id' => $userId,
                    'widget_name' => $widgetName,
                    'scope' => $scope,
                ],
                [
                    'order' => $index + 1,
                    'show_widget' => true,
                ]
            );
        }
    }

    /**
     * Hide widgets that were removed from dashboard.
     *
     * @param  array<string>  $sortedWidgets  Currently visible widget class names
     * @param  int  $userId  User ID
     * @param  string  $scope  Page scope
     */
    private function hideRemovedWidgets(array $sortedWidgets, int $userId, string $scope): void
    {
        $removedWidgetNames = $this->getRemovedWidgetNames($sortedWidgets);

        if (empty($removedWidgetNames)) {
            return;
        }

        UserWidgetPreference::where('user_id', $userId)
            ->where('scope', $scope)
            ->whereIn('widget_name', $removedWidgetNames)
            ->update(['show_widget' => false]);
    }

    /**
     * Refresh widget data after saving.
     */
    private function refreshWidgetData(): void
    {
        $this->visibleWidgets = $this->getSortedVisibleWidgets();
        $this->currentWidgets = $this->visibleWidgets;
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
     *
     * @return int|null
     */
    private function getUserId(): ?int
    {
        $resolver = config('dash-arrange.user_id_resolver', fn () => Auth::id());

        return $resolver();
    }

    /**
     * Check if there are unsaved changes.
     *
     * @return bool
     */
    private function hasUnsavedChanges(): bool
    {
        return $this->visibleWidgets !== $this->currentWidgets;
    }

    /**
     * Normalize column span value to integer.
     *
     * @param  mixed  $columnSpan  Column span value (int, string 'full', or array)
     * @param  int  $gridColumns  Total grid columns
     * @return int
     */
    private function normalizeColumnSpan(mixed $columnSpan, int $gridColumns): int
    {
        // Handle responsive array: ['sm' => 1, 'md' => 2, 'xl' => 'full']
        if (is_array($columnSpan)) {
            $mdSpan = $columnSpan['md'] ?? $columnSpan['default'] ?? reset($columnSpan) ?? self::DEFAULT_COLUMN_SPAN;

            if ($mdSpan === 'full') {
                return $gridColumns;
            }

            return is_numeric($mdSpan) && $mdSpan > 0
                ? min((int) $mdSpan, $gridColumns)
                : self::DEFAULT_COLUMN_SPAN;
        }

        // Handle string 'full'
        if ($columnSpan === 'full') {
            return $gridColumns;
        }

        // Handle numeric value
        if (is_numeric($columnSpan) && $columnSpan > 0) {
            return min((int) $columnSpan, $gridColumns);
        }

        return self::DEFAULT_COLUMN_SPAN;
    }

    /**
     * Get permission check closure from config.
     *
     * @return Closure
     */
    private function getPermissionCheckClosure(): Closure
    {
        return config('dash-arrange.permission_check', fn (string $widgetClass) => true);
    }

    /**
     * Check if widget is permitted for current user.
     *
     * @param  string  $widgetClass  Widget class name
     * @param  Closure  $permissionCheck  Permission check closure
     * @return bool
     */
    private function isWidgetPermitted(string $widgetClass, Closure $permissionCheck): bool
    {
        if (! class_exists($widgetClass)) {
            return false;
        }

        try {
            $resolvedWidget = resolve($widgetClass);

            // Check if widget has hasPermission method (FilamentShield)
            if (method_exists($resolvedWidget, 'hasPermission')) {
                return $resolvedWidget->hasPermission();
            }

            // Use custom permission check from config
            return $permissionCheck($widgetClass);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get user preferences from database.
     *
     * @param  int  $userId  User ID
     * @param  string  $scope  Page scope
     * @return array{all: array<string, bool>, visible: array<string, int>}
     */
    private function getUserPreferences(int $userId, string $scope): array
    {
        $allPreferences = UserWidgetPreference::where('user_id', $userId)
            ->where('scope', $scope)
            ->pluck('show_widget', 'widget_name')
            ->toArray();

        $visiblePreferences = UserWidgetPreference::where('user_id', $userId)
            ->where('scope', $scope)
            ->where('show_widget', true)
            ->orderBy('order')
            ->pluck('order', 'widget_name')
            ->toArray();

        return [
            'all' => $allPreferences,
            'visible' => $visiblePreferences,
        ];
    }

    /**
     * Determine if widget should be included based on preferences.
     *
     * @param  string  $widgetClass  Widget class name
     * @param  array{all: array<string, bool>, visible: array<string, int>}  $preferences  User preferences
     * @param  bool  $hasPreferences  Whether user has any preferences
     * @return bool
     */
    private function shouldIncludeWidget(string $widgetClass, array $preferences, bool $hasPreferences): bool
    {
        if (! $hasPreferences) {
            return true;
        }

        return isset($preferences['all'][$widgetClass]) && $preferences['all'][$widgetClass] === true;
    }

    /**
     * Get widget sort order.
     *
     * @param  string  $widgetClass  Widget class name
     * @param  array<string, int>  $visiblePreferences  Visible widget preferences with order
     * @return int
     */
    private function getWidgetSortOrder(string $widgetClass, array $visiblePreferences): int
    {
        if (isset($visiblePreferences[$widgetClass])) {
            return $visiblePreferences[$widgetClass];
        }

        try {
            $resolvedWidget = resolve($widgetClass);

            if (method_exists($resolvedWidget, 'getSort')) {
                return $resolvedWidget->getSort() ?? self::DEFAULT_SORT_ORDER;
            }
        } catch (Throwable $e) {
            // Fall through to default
        }

        return self::DEFAULT_SORT_ORDER;
    }

    /**
     * Determine widget visibility.
     *
     * @param  object  $resolvedWidget  Resolved widget instance
     * @param  bool|null  $forceVisible  Force visibility flag
     * @return bool
     */
    private function determineWidgetVisibility(object $resolvedWidget, ?bool $forceVisible): bool
    {
        if ($forceVisible === true) {
            return true;
        }

        $userId = $this->getUserId();

        if (! $userId) {
            return false;
        }

        $scope = $this->getWidgetScope();

        $preference = UserWidgetPreference::where('user_id', $userId)
            ->where('scope', $scope)
            ->where('widget_name', get_class($resolvedWidget))
            ->first();

        return $preference?->show_widget ?? false;
    }

    /**
     * Get widget title/heading.
     *
     * @param  object  $resolvedWidget  Resolved widget instance
     * @param  string  $widgetClass  Widget class name (fallback)
     * @return string
     */
    private function getWidgetTitle(object $resolvedWidget, string $widgetClass): string
    {
        $title = class_basename($widgetClass);

        foreach (self::WIDGET_TITLE_METHODS as $method) {
            if (method_exists($resolvedWidget, $method)) {
                try {
                    $value = $resolvedWidget->$method();

                    if ($value !== null && $value !== '') {
                        $title = $value;
                        break;
                    }
                } catch (Throwable $e) {
                    continue;
                }
            }
        }

        return $title;
    }

    /**
     * Get widget sort value.
     *
     * @param  object  $resolvedWidget  Resolved widget instance
     * @return int
     */
    private function getWidgetSort(object $resolvedWidget): int
    {
        if (method_exists($resolvedWidget, 'getSort')) {
            try {
                return $resolvedWidget->getSort() ?? 0;
            } catch (Throwable $e) {
                return 0;
            }
        }

        return 0;
    }

    /**
     * Find widget in array by name.
     *
     * @param  string  $widgetName  Widget class name
     * @param  array<array{name: string, sort: int, title: string, visible: bool}>  $widgets  Widget array
     * @return array{name: string, sort: int, title: string, visible: bool}|null
     */
    private function findWidgetInArray(string $widgetName, array $widgets): ?array
    {
        $index = $this->findWidgetIndex($widgetName, $widgets);

        return $index !== null ? $widgets[$index] : null;
    }

    /**
     * Find widget index in array by name.
     *
     * @param  string  $widgetName  Widget class name
     * @param  array<array{name: string, sort: int, title: string, visible: bool}>  $widgets  Widget array
     * @return int|null
     */
    private function findWidgetIndex(string $widgetName, array $widgets): ?int
    {
        $index = array_search($widgetName, array_column($widgets, 'name'), true);

        return $index !== false ? $index : null;
    }

    /**
     * Get names of widgets that were removed.
     *
     * @param  array<string>  $sortedWidgets  Currently visible widget names
     * @return array<string>
     */
    private function getRemovedWidgetNames(array $sortedWidgets): array
    {
        return array_column(
            array_filter(
                $this->currentWidgets,
                fn (array $widget) => ! in_array($widget['name'], $sortedWidgets, true)
            ),
            'name'
        );
    }
}
