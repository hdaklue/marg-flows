import Sortable from 'sortablejs';

document.addEventListener('alpine:init', () => {
    Alpine.directive('sortable', (el, { value, modifiers, expression }, { Alpine, effect, evaluate }) => {
        let sortableInstance = null;
        let debounceTimer = null;

        console.log('Initializing sortable for container:', el.dataset.container);

        // Get group name
        const groupName = el.dataset.sortableGroup || 'shared';
        
        // Create config
        const config = {
            group: groupName,
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            handle: el.querySelector('.sortable-handle') ? '.sortable-handle' : null,
            
            onEnd: function(evt) {
                console.log('Drag ended:', evt);
                
                if (expression) {
                    // Get all items in target container
                    const items = Array.from(evt.to.children)
                        .map(item => item.getAttribute('x-sortable:item'))
                        .filter(id => id);
                    
                    const eventData = {
                        to: evt.to.dataset.container,
                        from: evt.from.dataset.container,
                        oldIndex: evt.oldIndex,
                        newIndex: evt.newIndex,
                    };

                    console.log('Items:', items, 'Event:', eventData);
                    
                    const callback = evaluate(expression);
                    if (typeof callback === 'function') {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => {
                            callback(items, eventData);
                        }, 100);
                    }
                }
            }
        };

        // ACTUALLY CREATE THE SORTABLE INSTANCE
        console.log('Creating new Sortable for:', el.id || el.dataset.container);
        sortableInstance = new Sortable(el, config);
        
        console.log('Sortable created:', sortableInstance);

        // Cleanup
        return () => {
            if (sortableInstance) {
                sortableInstance.destroy();
            }
            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }
        };
    });

    // Item directive - just sets the attribute
    Alpine.directive('sortable:item', (el, { expression }) => {
        if (expression) {
            el.setAttribute('x-sortable:item', expression);
        }
    });

    // Handle directive
    Alpine.directive('sortable:handle', (el) => {
        el.classList.add('sortable-handle');
        el.style.cursor = 'grab';
    });
});