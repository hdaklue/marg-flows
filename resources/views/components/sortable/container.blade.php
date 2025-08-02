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
    @else
        @sortable:sort.window="
            console.log('SORTABLE:SORT EVENT:', $event.detail);
            // Handle the sort action based on event detail
            const { action, item, from, to } = $event.detail;
            if (action === 'move') {
                $wire.moveToColumn(item, to);
            } else if (action === 'reorder') {
                // Handle reorder within same column if needed
                console.log('Reorder within column:', from);
            }
        "
    @endif
>
    {{ $slot }}
</div>