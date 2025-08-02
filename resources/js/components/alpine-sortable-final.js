import Sortable from 'sortablejs';

document.addEventListener('alpine:init', () => {
    // Main sortable directive - ONLY for containers
    Alpine.directive('sortable', (el, { expression }, { evaluate }) => {
        console.log('Creating sortable for CONTAINER:', el, 'with children:', el.children);
        
        // Create Sortable instance for this container
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
                    // Get sorted item IDs from direct children
                    const items = Array.from(evt.to.children)
                        .map(child => child.getAttribute('data-sortable-item'))
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

    // Separate directive for items
    Alpine.directive('sortableitem', (el, { expression }) => {
        console.log('Setting up item:', el, 'with ID:', expression);
        el.setAttribute('data-sortable-item', expression);
    });

    // Separate directive for handles  
    Alpine.directive('sortablehandle', (el) => {
        console.log('Setting up handle:', el);
        el.classList.add('sortable-handle');
        el.style.cursor = 'grab';
    });
});