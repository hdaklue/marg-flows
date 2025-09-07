@props([
    'users' => [],
    'maxVisible' => 4,
    'size' => 'md', // 2xs, xs, sm, md, lg, xl
    'showCount' => true,
    'showTooltip' => true,
    'roleableType',
    'roleableKey',
    'scopeToKey',
    'scopeToType',
    'canEdit' => false,
])

@php
    $sizeClasses = [
        '2xs' => 'w-4 h-4 text-2xs',
        'xs' => 'w-6 h-6 text-xs',
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-12 h-12 text-sm',
        'xl' => 'w-16 h-16 text-base',
    ];

    $offsetClasses = [
        '2xs' => '-ms-1',
        'xs' => '-ms-1.5',
        'sm' => '-ms-2',
        'md' => '-ms-2.5',
        'lg' => '-ms-3',
        'xl' => '-ms-4',
    ];

    $borderClasses = [
        '2xs' => 'border-1',
        'xs' => 'border-1',
        'sm' => 'border-2',
        'md' => 'border-2',
        'lg' => 'border-2',
        'xl' => 'border-4',
    ];

    $visibleUsers = $users->take($maxVisible - ($showCount && count($users) > $maxVisible ? 1 : 0));
    $remainingCount = $users->count() - $visibleUsers->count();
@endphp

<div class="flex items-center" x-data="{
    tooltip: '',
    showTooltip: false,
    showUserTooltip(event, name) {
        this.tooltip = name;
        this.showTooltip = true;
    },
    hideUserTooltip() {
        this.showTooltip = false;
    }
}">

    @foreach ($visibleUsers as $index => $user)
        {{-- @php
            $userName =
                is_array($user) && isset($user['name'])
                    ? $user['name']
                    : (is_object($user) && isset($user->name)
                        ? $user->name
                        : 'User');
            $userAvatar =
                is_array($user) && isset($user['avatar'])
                    ? $user['avatar']
                    : (is_object($user) && isset($user->avatar)
                        ? $user->avatar
                        : null);
        @endphp --}}
        <div class="{{ $index > 0 ? $offsetClasses[$size] ?? '' : '' }} group relative">
            @if ($user)
                <img src="{{ $user->avatarUrl }}" alt="{{ $user->name }}"
                    class="{{ $sizeClasses[$size] ?? '' }} {{ $borderClasses[$size] ?? '' }} cursor-pointer rounded-full border-white object-cover shadow-sm ring-2 ring-white transition-all duration-200 hover:z-10 hover:scale-110 dark:border-gray-700 dark:ring-gray-800">
            @else
                <div
                    class="{{ $sizeClasses[$size] ?? '' }} {{ $borderClasses[$size] ?? '' }} flex cursor-pointer items-center justify-center rounded-full border-white bg-gradient-to-br from-blue-500 to-purple-600 font-semibold text-white shadow-sm ring-2 ring-white transition-all duration-200 hover:z-10 hover:scale-110 dark:border-gray-700 dark:ring-gray-800">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            @if ($showTooltip)
                <!-- CSS-only tooltip -->
                <div
                    class="absolute z-50 px-2 py-1 mb-2 text-xs text-white transition-opacity duration-200 transform -translate-x-1/2 bg-gray-900 rounded shadow-lg opacity-0 pointer-events-none bottom-full left-1/2 whitespace-nowrap group-hover:opacity-100 dark:bg-gray-700">
                    {{ $user->name }}
                </div>
            @endif
        </div>
    @endforeach

    @if ($showCount)
        <div class="{{ $offsetClasses[$size] ?? '' }} group relative"
            @if ($canEdit) wire:click="$dispatch('open-members-modal',{roleableKey: '{{ $roleableKey }}', roleableType: '{{ $roleableType }}', scopeToKey: '{{ $scopeToKey }}', scopeToType: '{{ $scopeToType }}'  })" @endif>
            <div
                class="{{ $sizeClasses[$size] ?? '' }} {{ $borderClasses[$size] ?? '' }} flex cursor-pointer items-center justify-center rounded-full border-white bg-gray-100 font-semibold text-gray-600 shadow-sm ring-2 ring-white transition-all duration-200 hover:z-10 hover:scale-110 hover:bg-gray-200 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300 dark:ring-gray-800 dark:hover:bg-gray-600">
                @if ($remainingCount > 0)
                    +{{ $remainingCount }}
                @else
                    <x-heroicon-o-users class="w-3 h-3" />
                @endif
            </div>
            @if ($showTooltip)
                <!-- CSS-only tooltip -->
                <div
                    class="absolute z-50 px-2 py-1 mb-2 text-xs text-white transition-opacity duration-200 transform -translate-x-1/2 bg-gray-900 rounded shadow-lg opacity-0 pointer-events-none bottom-full left-1/2 whitespace-nowrap group-hover:opacity-100 dark:bg-gray-700">
                    @if ($remainingCount > 0)
                        {{ $remainingCount }} more user{{ $remainingCount > 1 ? 's' : '' }}
                    @else
                        Manage members
                    @endif
                </div>
            @endif
        </div>
    @endif

</div>
