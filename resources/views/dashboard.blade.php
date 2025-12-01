<x-filament::page>
    <div
        x-data="{
            editable: false,

            sortableInstance: null,

            customizeDashboard(){
                this.editable = true;
                this.enableSorting();
            },

            revertChanges(){
                @this.call('revertChanges');
                this.editable = false;
                this.disableSorting();
            },

            saveChanges(){
                @this.call('updateUserWidgetPreferences', this.getSortedUiWidgets());
                this.editable = false;
                this.disableSorting();
            },

            enableSorting() {
                $nextTick(() => {
                    let container = document.querySelector('#sortable-container');
                    if (container && !this.sortableInstance) {
                        this.sortableInstance = Sortable.create(container, {
                            animation: {{ config('customize-dashboard-widget.sortable_options.animation', 150) }},
                            handle: '{{ config('customize-dashboard-widget.sortable_options.handle', '[x-sortable-handle]') }}',
                        });
                    }
                });
            },

            disableSorting() {
                if (this.sortableInstance) {
                    this.sortableInstance.destroy();
                    this.sortableInstance = null;
                }
            },

            handleWidgetDropEvent(event) {
                @this.call('updateCurrentWidgets', this.getSortedUiWidgets());
            },

            handleCheckboxChange(event, widget) {
                event.target.checked ? @this.call('addWidget', widget) : @this.call('removeWidget', widget);
            },

            getSortedUiWidgets(){
                let sortedWidgets = [];

                document.querySelectorAll('[x-sortable-item]').forEach((item) => {
                    sortedWidgets.push(item.getAttribute('x-sortable-item'));
                });

                return sortedWidgets;
            }
        }"
    >
        <div class="flex justify-between mb-4">
            <div class="text-left">
                <h2 class="text-2xl font-bold">{{ $this->getTitle() ?: __('Dashboard') }}</h2>
            </div>
            <div class="text-right space-x-1">
                <x-filament::button
                    color="primary"
                    x-show="!editable"
                    x-on:click="customizeDashboard()"
                    size="sm"
                >
                    {{ __('Customize My Dashboard') }}
                </x-filament::button>

                <x-filament::button
                    color="primary"
                    x-show="editable"
                    x-on:click="saveChanges()"
                    size="sm"
                >
                    {{ __('Save') }}
                </x-filament::button>

                <x-filament::button
                    color="gray"
                    size="sm"
                    x-show="editable"
                    x-on:click="revertChanges()"
                >
                    {{ __('Cancel') }}
                </x-filament::button>
            </div>
        </div>

        <div>
            <div class="p-4 bg-white dark:bg-gray-800 mb-4 space-y-3 rounded" x-show="editable">
              <div>
                <span class="font-medium text-lg">{{ __('Available Widgets') }}</span>
              </div>
              <div class="grid grid-cols-4 gap-4">
                @foreach ($this->permittedWidgets as $index => $widget)
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="permittedWidgets.{{ $index }}.visible" class="sr-only peer" x-on:change="handleCheckboxChange(event,'{{ str_replace('\\', '\\\\', $widget['name']) }}')">
                        <div class="relative min-w-11 w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600"></div>
                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300 select-none">{{ $widget['title'] }}</span>
                    </label>
                @endforeach
              </div>
            </div>
        </div>

        <div
            id="sortable-container"
            class="grid grid-cols-1 md:grid-cols-{{ config('customize-dashboard-widget.default_grid_columns.md', 2) }} gap-4"
            x-bind:data-sortable="editable ? 'true' : 'false'"
        >
            @foreach ($this->currentWidgets as $widget)
                @if ($widget['visible'])
                    @php
                        $widgetInstance = resolve($widget['name']);
                        
                        // Priority 1: Use custom getWidgetWidthClass() if available
                        if (method_exists($widgetInstance, 'getWidgetWidthClass')) {
                            $widthClass = $widgetInstance->getWidgetWidthClass();
                        }
                        // Priority 2: Convert Filament's getColumnSpan() to Tailwind classes
                        elseif (method_exists($widgetInstance, 'getColumnSpan')) {
                            $columnSpan = $widgetInstance->getColumnSpan();
                            $widthClass = is_array($columnSpan) 
                                ? collect($columnSpan)->map(fn($span, $breakpoint) => 
                                    $span === 'full' 
                                        ? "{$breakpoint}:col-span-full" 
                                        : "{$breakpoint}:col-span-{$span}"
                                )->implode(' ')
                                : "md:col-span-{$columnSpan}";
                        }
                        // Priority 3: Default fallback
                        else {
                            $widthClass = 'md:col-span-1';
                        }
                    @endphp
                    <div
                        x-sortable-item="{{ $widget['name'] }}"
                        x-sortable-handle="drag-handle"
                        x-on:drag="(event) => {
                            if (event.clientY > window.innerHeight - 200) {
                                window.scrollBy(0, 3);
                            } else if (event.clientY < 200) {
                                window.scrollBy(0, -3);
                            }
                        }"
                        x-on:dragend="handleWidgetDropEvent(event)"
                        class="{{ $widthClass }} relative fi-wi"
                    >
                        <div x-bind:class="{'select-none relative p-2 pointer-events-none': editable}">
                            @livewire($widget['name'], [], key($widget['name'] . '-'. auth()->id().time()))
                        </div>

                        <span class="drag-handle cursor-grab absolute top-0 left-0 z-1 hover:ring-2 dark:ring-gray-500 ring-primary-500 dark:bg-white/10 bg-white/40 transition-all duration-450 ease-in-out rounded-xl w-full h-full" x-show="editable"></span>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</x-filament::page>

