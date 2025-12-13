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
                            animation: {{ config('dash-arrange.sortable_options.animation', 150) }},
                            handle: '{{ config('dash-arrange.sortable_options.handle', '[x-sortable-handle]') }}',
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
        <div class="flex justify-between items-center dashboard-header">
            <div>
                <h2 class="text-2xl font-bold">{{ $this->getTitle() ?: __('Dashboard') }}</h2>
            </div>
            <div class="flex items-center gap-2 ml-auto">
                <x-filament::button
                    color="{{ config('dash-arrange.customize_dashboard_button_color', 'primary') }}"
                    x-show="!editable"
                    x-on:click="customizeDashboard()"
                    size="sm"
                >
                    {{ config('dash-arrange.customize_dashboard_title', 'Customize My Dashboard') }}
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
            <div class="available-widgets-section space-y-3" x-show="editable">
              <div>
                <span class="available-widgets-title font-medium text-lg">{{ __('Available Widgets') }}</span>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                @foreach ($this->permittedWidgets as $index => $widget)
                    <label class="flex items-center cursor-pointer w-full min-w-0 gap-3">
                        <input type="checkbox" wire:model="permittedWidgets.{{ $index }}.visible" class="sr-only peer" x-on:change="handleCheckboxChange(event,'{{ str_replace('\\', '\\\\', $widget['name']) }}')">
                        <div class="relative flex-shrink-0 min-w-11 w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600"></div>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-300 select-none break-words flex-1">{{ $widget['title'] }}</span>
                    </label>
                @endforeach
              </div>
            </div>
        </div>

        @php
            $columns = $this->getColumns();
            $gridColumns = is_array($columns) ? ($columns['md'] ?? $columns['default'] ?? 2) : (int) $columns;
            $gridColumns = max(1, min(12, $gridColumns)); // Ensure valid range (1-12)
        @endphp

        <style>
            #sortable-container {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
            @media (min-width: 768px) {
                #sortable-container {
                    grid-template-columns: repeat({{ $gridColumns }}, minmax(0, 1fr));
                }
            }
        </style>

        <div
            id="sortable-container"
            class="grid gap-4"
            x-bind:data-sortable="editable ? 'true' : 'false'"
        >
            @foreach ($this->currentWidgets as $widget)
                @if ($widget['visible'])
                    @php
                        try {
                            $widgetInstance = resolve($widget['name']);
                            $columnSpan = $this->getWidgetColumnSpan($widgetInstance, $gridColumns);
                        } catch (\Throwable $e) {
                            // Fallback if widget can't be resolved
                            $columnSpan = 1;
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
                        class="relative"
                        style="grid-column: span {{ $columnSpan }} / span {{ $columnSpan }};"
                    >
                        <div x-bind:class="{'select-none relative p-2 pointer-events-none': editable}">
                            @livewire($widget['name'], [], key($widget['name'] . '-'. auth()->id().time()))
                        </div>

                        <span class="drag-handle cursor-grab absolute top-0 left-0 z-10 hover:ring-2 dark:ring-gray-500 ring-primary-500 dark:bg-white/10 bg-white/40 transition-all duration-450 ease-in-out rounded-xl w-full h-full" x-show="editable"></span>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</x-filament::page>

