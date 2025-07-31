@props([
    'statePath' => '',
    'options' => [],
    'placeholder' => 'Choose option...',
    'label' => null,
    'size' => 'md',
    'disabled' => false,
    'required' => false,
    'error' => null,
    'class' => '',
    'allowColors' => false,
    'iconOnly' => false,
])

@php
    $uid = Str::uuid();

    // Size variants
    $sizeClasses = [
        'sm' => [
            'button' => 'px-2.5 py-1.5 text-sm',
            'icon' => 'size-4',
            'checkIcon' => 'size-4',
            'checkContainer' => 'w-5',
            'options' => 'p-1',
            'option' => 'px-2 py-1 text-sm',
        ],
        'md' => [
            'button' => 'px-3 py-2 text-sm',
            'icon' => 'size-5',
            'checkIcon' => 'size-5',
            'checkContainer' => 'w-6',
            'options' => 'p-1.5',
            'option' => 'px-2 py-1.5 text-sm',
        ],
        'lg' => [
            'button' => 'px-4 py-2.5 text-base',
            'icon' => 'size-5',
            'checkIcon' => 'size-5',
            'checkContainer' => 'w-6',
            'options' => 'p-2',
            'option' => 'px-3 py-2 text-base',
        ],
    ];

    $classes = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div x-data="{
    value: $wire.entangle('{{ $statePath }}'),
    isIconOnly: @js($iconOnly),
    options: @js($options),
    allowColors: {{ $allowColors ? 'true' : 'false' }},
    isTouchDevice: false,
    showModal: false,
    get selectedOption() {
        // Only compute if we have a valid value and options
        if (!this.value || !this.options || this.options.length === 0) {
            return null;
        }
        return this.options.find(option => option.value == this.value) || null;
    },
    get currentColor() {
        if (!this.allowColors || !this.selectedOption || !this.selectedOption.color) {
            return 'zinc';
        }
        return this.selectedOption.color;
    },
    init() {
        // Detect touch device
        this.isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    },
    selectOption(optionValue) {
        this.value = optionValue;
        this.showModal = false;
    }
}" class="{{ $class }}">

    @if ($label)
        <label for="{{ $uid }}" class="block mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}
            @if ($required)
                <span class="ml-1 text-red-500">*</span>
            @endif
        </label>
    @endif

    <!-- Listbox -->
    <div x-listbox x-model="value" class="relative">
        @if ($label)
            <label x-listbox:label class="sr-only">{{ $label }}</label>
        @endif

        <!-- Button -->
        <button x-listbox:button @if ($disabled) disabled @endif
            :class="isIconOnly ?
                'p-1.5 group flex w-auto items-center justify-center rounded-lg border shadow-sm transition-colors duration-200 focus:outline-none focus:ring-1' :
                '{{ $classes['button'] }} group flex w-auto items-center justify-between gap-2 rounded-lg border shadow-sm transition-colors duration-200 focus:outline-none focus:ring-1'"
            class="@if ($disabled) border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-500 cursor-not-allowed
            @elseif($error)
                border-red-300 dark:border-red-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 hover:border-red-400 dark:hover:border-red-500 focus:ring-red-500/20
            @else
                bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 focus:ring-sky-500/20 @endif"
            :class="{
                @if (!$disabled && !$error) ['border-' + currentColor + '-200 dark:border-' + currentColor + '-700']: allowColors && selectedOption && selectedOption.color,
                    ['hover:border-' + currentColor + '-300 dark:hover:border-' + currentColor + '-600']: allowColors && selectedOption && selectedOption.color,
                    ['focus:border-' + currentColor + '-500 dark:focus:border-' + currentColor + '-400']: allowColors && selectedOption && selectedOption.color,
                    ['focus:ring-' + currentColor + '-500/20']: allowColors && selectedOption && selectedOption.color,
                    'focus:border-sky-500 dark:focus:border-sky-400': !allowColors || !selectedOption || !selectedOption.color @endif
            }">
            <!-- Selected option display -->
            <div class="flex items-center flex-1 min-w-0"
                :class="(selectedOption && selectedOption.icon) || (allowColors && selectedOption && selectedOption.color) ?
                'gap-2' : ''">
                <!-- Selected option icon (only if exists) -->
                <template x-if="selectedOption && selectedOption.icon">
                    <div x-html="selectedOption.icon"
                        class="{{ $classes['checkIcon'] }} shrink-0 text-zinc-500 dark:text-zinc-400"></div>
                </template>

                <!-- Status color square (when no icon but color exists) -->
                <template x-if="allowColors && selectedOption && selectedOption.color && !selectedOption.icon">
                    <div x-tooltip="isIconOnly && selectedOption.label"
                        class="{{ $classes['checkIcon'] }} shrink-0 rounded-md border border-opacity-20"
                        :class="'bg-' + selectedOption.color + '-500 border-' + selectedOption.color + '-600'"></div>
                </template>

                <!-- Selected option text -->
                @if (!$iconOnly)
                    <span x-text="selectedOption ? selectedOption.label : '{{ $placeholder }}'"
                        class="text-left truncate"
                        :class="!selectedOption ? 'text-zinc-400 dark:text-zinc-500' :
                            (allowColors && selectedOption && selectedOption.color ?
                                'text-' + selectedOption.color + '-700 dark:text-' + selectedOption.color + '-300' :
                                'text-zinc-900 dark:text-zinc-100')"></span>
                @endif
            </div>

            @if (!$iconOnly)
                <svg class="{{ $classes['icon'] }} @if ($disabled) text-zinc-300 dark:text-zinc-600
                @else
                    text-zinc-400 dark:text-zinc-500 group-hover:text-zinc-600 dark:group-hover:text-zinc-400 @endif shrink-0 transition-colors duration-200"
                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z"
                        clip-rule="evenodd" />
                </svg>
            @endif
        </button>

        <!-- Backdrop for touch devices -->
        <div x-show="$listbox.isOpen && isTouchDevice" x-cloak class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
            @click="$listbox.button.click()"></div>

        <!-- Options -->
        <ul x-listbox:options x-cloak
            :class="isTouchDevice ?
                'fixed inset-x-4 bottom-4 z-50 max-h-96 overflow-y-auto overscroll-contain rounded-xl border border-zinc-200 bg-white shadow-2xl outline-none dark:border-zinc-700 dark:bg-zinc-900 p-2 space-y-1' :
                '{{ $classes['options'] }} absolute left-0 z-10 mt-2 max-h-80 w-full overflow-y-auto overscroll-contain rounded-lg border border-zinc-200 bg-white shadow-lg outline-none dark:border-zinc-700 dark:bg-zinc-900'">
            <template x-for="option in options" :key="option.value">
                <li x-listbox:option :value="option.value" :disabled="option.disabled"
                    :class="{
                        ['bg-' + option.color + '-50 dark:bg-' + option.color + '-900/20 text-' + option.color +
                            '-700 dark:text-' + option.color + '-300'
                        ]: $listboxOption.isActive && !$listboxOption.isDisabled && allowColors && option.color,
                            'bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-300': $listboxOption.isActive && !
                            $listboxOption.isDisabled && (!allowColors || !option.color),
                            'text-zinc-900 dark:text-zinc-100': !$listboxOption.isActive && !$listboxOption.isDisabled,
                            'text-zinc-400 dark:text-zinc-500 cursor-not-allowed': $listboxOption.isDisabled,
                            'px-4 py-2 text-base': isTouchDevice,
                            '{{ $classes['option'] }}': !isTouchDevice,
                            'flex items-center w-full transition-colors duration-150 rounded-md cursor-default group':
                            true
                    }"
                    <!-- Icon or Color Square Container -->
                    <template x-if="option.icon || (allowColors && option.color)">
                        <div class="{{ $classes['checkContainer'] }} flex shrink-0 items-center justify-center">
                            <!-- Custom Icon (if provided) -->
                            <template x-if="option.icon">
                                <div x-html="option.icon"
                                    class="{{ $classes['checkIcon'] }} shrink-0 text-zinc-500 dark:text-zinc-400">
                                </div>
                            </template>

                            <!-- Status color square (when no icon but color exists) -->
                            <template x-if="allowColors && option.color && !option.icon">
                                <div class="{{ $classes['checkIcon'] }} shrink-0 rounded-md border border-opacity-20"
                                    :class="'bg-' + option.color + '-500 border-' + option.color + '-600'"></div>
                            </template>
                        </div>
                    </template>

                    <!-- Label -->
                    <div class="flex items-center flex-1 min-w-0 gap-2">
                        <span x-text="option.label" class="truncate"></span>
                    </div>

                    <!-- Selected Checkmark (at the end) -->
                    <template x-if="$listboxOption.isSelected">
                        <div class="{{ $classes['checkContainer'] }} flex shrink-0 items-center justify-center">
                            <svg class="{{ $classes['checkIcon'] }} shrink-0"
                                :class="allowColors && option.color ?
                                    'text-' + option.color + '-500 dark:text-' + option.color + '-400' :
                                    'text-sky-500 dark:text-sky-400'"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </template>
                </li>
            </template>
        </ul>
    </div>

    @if ($error)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
</div>
