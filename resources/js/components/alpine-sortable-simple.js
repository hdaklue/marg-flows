import Sortable from 'sortablejs';

document.addEventListener('alpine:init', () => {
    Alpine.directive('sortable', (el, { modifiers, expression }, { evaluate }) => {
        // Only run on containers with x-sortable (not x-sortable:item or x-sortable:handle)
        if (modifiers.length > 0) {
            return; // Skip if it has modifiers like :item or :handle
        }
        
        // Must have expression (the Livewire method)
        if (!expression) {
            return;
        }
        
        console.log('Creating sortable for CONTAINER:', el, 'with children:', el.children);
        
        // Simple: each x-sortable container gets new Sortable()
        const sortable = new Sortable(el, {
            group: el.dataset.sortableGroup || 'shared',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            
            onStart: function(evt) {
                console.log('Drag started:', evt.item);
            },
            
            onEnd: function(evt) {
                console.log('Drag ended:', evt);
                
                if (expression) {
                    // Get sorted item IDs
                    const items = Array.from(evt.to.children)
                        .map(child => child.getAttribute('x-sortable:item'))
                        .filter(Boolean);
                    
                    console.log('Sorted items:', items);
                    
                    // Call Livewire method
                    const callback = evaluate(expression);
                    if (typeof callback === 'function') {
                        callback(items, {
                            from: evt.from.dataset.container,
                            to: evt.to.dataset.container,
                            oldIndex: evt.oldIndex,
                            newIndex: evt.newIndex
                        });
                    }
                }
            }
        });

        console.log('Sortable created:', sortable);
        return () => sortable.destroy();
    });

    Alpine.directive('sortable:item', (el, { expression }) => {
        el.setAttribute('x-sortable:item', expression);
    });
});