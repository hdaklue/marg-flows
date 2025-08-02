import Sortable from 'sortablejs';

document.addEventListener('alpine:init', () => {
    // x-sortable-group creates new Sortable instances
    Alpine.directive('sortable-group', (el, { expression }, { evaluate }) => {
        // Get group name from x-sortable attribute value
        const groupName = el.getAttribute('x-sortable') || 'shared';
        
        console.log('Creating Sortable for group:', groupName, 'on element:', el);
        
        // Create new Sortable instance for this group
        const sortable = new Sortable(el, {
            group: groupName, // Use x-sortable value as group name
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

    // x-sortable:item just marks items as draggable
    Alpine.directive('sortable:item', (el, { expression }) => {
        el.setAttribute('x-sortable:item', expression);
    });
});