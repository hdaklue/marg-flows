@props([
    'groupName' => 'shared',
    'onSort' => null,
    'class' => '',
])

<div 
    {{ $attributes->merge(['class' => $class]) }}
    x-sortable="{{ $groupName }}"
    @if($onSort)
        @sortable:sort.window="{{ $onSort }}"
    @endif
>
    {{ $slot }}
</div>