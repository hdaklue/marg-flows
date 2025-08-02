import Sortable from 'sortablejs';


// Store Sortable instances globally
window.sortableInstances = window.sortableInstances || new Map();

document.addEventListener('alpine:init', () => {
    // x-sortable directive with on-sort and on-drag handlers
    Alpine.directive('sortable', (el, { expression }, { evaluate, cleanup }) => {
        // Get handlers from attributes
        const onSortHandler = el.getAttribute('on-sort');
        const onDragHandler = el.getAttribute('on-drag');

        // Store handlers globally for sortable groups to access
        if (!window.sortableHandlers) {
            window.sortableHandlers = new Map();
        }

        const groupName = expression || el.getAttribute('x-sortable') || 'shared';

        console.log('Setting up sortable handlers for group:', groupName, {
            onSort: onSortHandler,
            onDrag: onDragHandler
        });

        window.sortableHandlers.set(groupName, {
            onSort: onSortHandler ? (data) => evaluate(onSortHandler, { $event: { detail: data } }) : null,
            onDrag: onDragHandler ? (data) => evaluate(onDragHandler, { $event: { detail: data } }) : null
        });

        console.log('Handlers registered:', window.sortableHandlers.get(groupName));

        cleanup(() => {
            console.log('Cleaning up handlers for group:', groupName);
            window.sortableHandlers.delete(groupName);
        });
    });

    // x-sortable-group creates new Sortable instances
    Alpine.directive('sortable-group', (el, { expression }) => {
        // Find parent with x-sortable attribute to get the group name
        let groupName = 'shared';
        let parent = el.parentElement;
        while (parent && !parent.hasAttribute('x-sortable')) {
            parent = parent.parentElement;
        }
        if (parent) {
            groupName = parent.getAttribute('x-sortable');
        }

        console.log('Creating Sortable for element ID:', el.id, 'with group:', groupName);

        // Clean up existing instance if it exists
        if (window.sortableInstances.has(el.id)) {
            const existingInstance = window.sortableInstances.get(el.id);
            if (existingInstance) {
                console.log('Cleaning up existing Sortable instance for:', el.id);
                existingInstance.destroy();
                window.sortableInstances.delete(el.id);
            }
        }

        // Create new Sortable instance: new Sortable('element-id', {group: 'parent-x-sortable-value'})
        const sortable = new Sortable(el, {
            group: groupName, // Use group name from parent x-sortable
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            handle: '.sortable-handle', // Only drag by handle if present

            onStart: function (evt) {
                console.log('Drag started:', evt.item);

                const dragData = {
                    item: evt.item.getAttribute('x-sortable:item') || evt.item.dataset.sortableItem,
                    from: evt.from.dataset.container,
                    oldIndex: evt.oldIndex
                };

                // Call custom on-drag handler if exists
                const handlers = window.sortableHandlers?.get(groupName);
                console.log('Looking for drag handlers:', groupName, handlers);
                if (handlers?.onDrag) {
                    console.log('Calling onDrag handler');
                    handlers.onDrag(dragData);
                } else {
                    console.log('No onDrag handler found');
                }

                // Dispatch global onDrag event
                window.dispatchEvent(new CustomEvent('sortable:drag', {
                    detail: dragData
                }));
            },

            onEnd: function (evt) {
                console.log('Drag ended:', evt);

                // Get sorted item IDs from direct children only
                const items = Array.from(evt.to.children)
                    .map(child => child.getAttribute('x-sortable:item') || child.dataset.sortableItem)
                    .filter(Boolean);

                console.log('Sorted items:', items);

                const sortData = {
                    items: items,
                    from: evt.from.dataset.container,
                    to: evt.to.dataset.container,
                    oldIndex: evt.oldIndex,
                    newIndex: evt.newIndex,
                    item: evt.item.getAttribute('x-sortable:item') || evt.item.dataset.sortableItem
                };
                console.log('sortData:', sortData);

                // Call custom on-sort handler if exists
                // const handlers = window.sortableHandlers?.get(groupName);
                // if (handlers?.onSort) {

                //     handlers.onSort(sortData);
                // }

                // Dispatch global onSort event
                window.dispatchEvent(new CustomEvent('sortable:sort', { detail: { payload: sortData } }));
                // window.Livewire.dispatch('sortable:sort', sortData);
                console.log(window.Livewire);

            }
        });

        console.log('Sortable created:', sortable);

        // Store instance globally
        window.sortableInstances.set(el.id, sortable);

        // Clean up on element removal
        return () => {
            if (sortable) {
                sortable.destroy();
                window.sortableInstances.delete(el.id);
            }
        };
    });

    // x-sortable:item just marks items as draggable (no functionality, just data attribute)
    Alpine.directive('sortable:item', (el, { expression }) => {
        el.setAttribute('x-sortable:item', expression);
        el.setAttribute('data-sortable-item', expression);

        // Make sure item is draggable
        if (!el.hasAttribute('draggable')) {
            el.setAttribute('draggable', 'true');
        }
    });

    // x-sortable:handle marks elements as drag handles
    Alpine.directive('sortable:handle', (el) => {
        el.classList.add('sortable-handle');
        el.style.cursor = 'grab';

        el.addEventListener('mousedown', () => {
            el.style.cursor = 'grabbing';
        });

        el.addEventListener('mouseup', () => {
            el.style.cursor = 'grab';
        });
    });
});
