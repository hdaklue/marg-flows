<div x-data="{ handle: (el, item, position) => { console.log(el, item, position); } }" class="mx-auto flex h-1/2 w-2/3 flex-col items-center justify-center pt-12">
    <h2 class="dark:text-gray-200">Kanban Board</h2>
    <div class="flex w-full flex-row gap-3" x-sort="handle">
        <div x-sort.ghost="handle($el,$item,$position)" data-stage="asd" x-sort:group="active"
            class="mx-auto flex w-1/2 flex-col gap-4 dark:text-gray-200">
            <div x-sort:item="1" class="bg-zinc-700 p-2">Item</div>
            <div x-sort:item="2" x-sort:item class="bg-zinc-700 p-2">Item -2</div>
        </div>
        <div x-sort.ghost="handle($el,$item,$position)" x-sort:group="active"
            class="mx-auto flex w-1/2 flex-col gap-4 dark:text-gray-200">
            <div x-sort:item="1"class="bg-zinc-700 p-2 [body:not(.sorting)_&]:hover:border">active item -1</div>
            <div x-sort:item="2" x-sort:item class="bg-zinc-700 p-2">Item -2</div>
            <div x-sort:item="3" x-sort:item class="bg-zinc-700 p-2">Item -2</div>
            <div x-sort:item="4" x-sort:item class="bg-zinc-700 p-2">Item -2</div>
        </div>
    </div>
</div>
