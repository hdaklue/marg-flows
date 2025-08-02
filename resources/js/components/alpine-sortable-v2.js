import Sortable from 'sortablejs';

document.addEventListener('alpine:init', () => {
    // Store all sortable instances globally to share the same group
    const sortableInstances = new Map();
    
    Alpine.directive('sortable', (el, { value, modifiers, expression }, { Alpine, effect, evaluate }) => {
        let debounceTimer = null;
        
        // Get the group name from data-sortable-group
        const groupName = el.dataset.sortableGroup || 'shared';
        
        const config = {
            group: groupName, // All containers with same group can exchange items
            sort: true, // Enable sorting inside list
            delay: 0, // No delay by default
            delayOnTouchOnly: false, 
            touchStartThreshold: 0,
            disabled: false,
            animation: 150,
            easing: "cubic-bezier(1, 0, 0, 1)",
            handle: null, // Will be set below if needed
            filter: '.sortable-disabled', // Elements that shouldn't be draggable
            preventOnFilter: true,
            draggable: '[x-sortable\\:item]', // Only items with this attribute are draggable
            
            dataIdAttr: 'x-sortable:item', // Use our custom attribute for IDs
            
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen', 
            dragClass: 'sortable-drag',
            
            swapThreshold: 1,
            invertSwap: false,
            invertedSwapThreshold: 1,
            
            forceFallback: false,
            fallbackClass: 'sortable-fallback',
            fallbackOnBody: true,
            fallbackTolerance: 0,
            
            dragoverBubble: false,
            removeCloneOnHide: true,
            emptyInsertThreshold: 5,
            
            // Event handlers
            onStart: function(evt) {
                evt.item.classList.add('sortable-dragging');
            },
            
            onEnd: function(evt) {
                evt.item.classList.remove('sortable-dragging');
                
                if (expression) {
                    // Get all items in the target container
                    const items = Array.from(evt.to.children)
                        .map(item => item.getAttribute('x-sortable:item') || item.dataset.sortableId || item.id)
                        .filter(id => id); // Remove empty values
                    
                    const eventData = {
                        item: evt.item,
                        to: evt.to.dataset.container || 'unknown',
                        from: evt.from.dataset.container || 'unknown',
                        oldIndex: evt.oldIndex,
                        newIndex: evt.newIndex,
                        toElement: evt.to,
                        fromElement: evt.from
                    };

                    const callback = evaluate(expression);
                    if (typeof callback === 'function') {
                        // Debounce the callback
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => {
                            callback(items, eventData);
                        }, 100);
                    }
                }
            },
            
            onAdd: function(evt) {
                // Item was added from another list
            },
            
            onUpdate: function(evt) {
                // Item was moved within the same list
            },
            
            onRemove: function(evt) {
                // Item was removed to another list
            },
            
            onSort: function(evt) {
                // Any change in order
            },
            
            onClone: function(evt) {
                // Item was cloned
            },
            
            onFilter: function(evt) {
                // Filtered item was attempted to be dragged
            },
            
            onMove: function(evt, originalEvent) {
                // Callback on dragging
                return true; // allow move
            },
            
            onChoose: function(evt) {
                // Item is chosen for dragging
            },
            
            onUnchoose: function(evt) {
                // Item is no longer chosen
            }
        };

        // Override config with data attributes
        if (el.dataset.sortableAnimation) {
            config.animation = parseInt(el.dataset.sortableAnimation);
        }
        if (el.dataset.sortableDelay) {
            config.delay = parseInt(el.dataset.sortableDelay);
        }
        // Set up handle if specified
        if (el.dataset.sortableHandle) {
            config.handle = el.dataset.sortableHandle;
        } else {
            // Check if any items have handle directives
            const hasHandles = el.querySelector('.sortable-handle');
            if (hasHandles) {
                config.handle = '.sortable-handle';
            }
        }
        if (el.dataset.sortableFilter) {
            config.filter = el.dataset.sortableFilter;
        }
        if (el.dataset.sortableGhostClass) {
            config.ghostClass = el.dataset.sortableGhostClass;
        }

        // Initialize SortableJS - each data-container gets its own instance
        function initSortable() {
            const containerId = el.dataset.container || el.id || ('sortable-' + Math.random().toString(36).substr(2, 9));
            
            // Destroy existing instance for this container
            if (sortableInstances.has(containerId)) {
                sortableInstances.get(containerId).destroy();
            }
            
            // Ensure element has an ID
            if (!el.id) {
                el.id = containerId;
            }
            
            // Create new Sortable instance for this specific data-container
            const instance = new Sortable(el, config);
            sortableInstances.set(containerId, instance);
        }

        // Setup accessibility - minimal to avoid conflicts with SortableJS
        function setupAccessibility() {
            const items = el.querySelectorAll('[x-sortable\\:item]');
            items.forEach((item, index) => {
                if (!item.hasAttribute('aria-describedby')) {
                    item.setAttribute('aria-describedby', 'sortable-instructions');
                }
            });

            // Add instructions for screen readers
            if (!document.getElementById('sortable-instructions')) {
                const instructions = document.createElement('div');
                instructions.id = 'sortable-instructions';
                instructions.className = 'sr-only';
                instructions.textContent = 'Draggable item. Use mouse or touch to drag and reorder.';
                document.body.appendChild(instructions);
            }
        }

        // Initialize
        initSortable();
        setupAccessibility();

        // Watch for changes
        effect(() => {
            setupAccessibility();
        });

        // Cleanup
        return () => {
            const containerId = el.dataset.container || el.id;
            if (containerId && sortableInstances.has(containerId)) {
                sortableInstances.get(containerId).destroy();
                sortableInstances.delete(containerId);
            }
            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }
        };
    });

    // Helper directive for sortable items
    Alpine.directive('sortable:item', (el, { expression }) => {
        if (expression) {
            // Set the item identifier - SortableJS will handle draggable
            el.setAttribute('x-sortable:item', expression);
            // Don't add draggable="true" - let SortableJS handle this
        }
    });

    // Helper directive for drag handles
    Alpine.directive('sortable:handle', (el) => {
        el.style.cursor = 'grab';
        
        el.addEventListener('mousedown', () => {
            el.style.cursor = 'grabbing';
        });
        
        el.addEventListener('mouseup', () => {
            el.style.cursor = 'grab';
        });
        
        el.addEventListener('mouseleave', () => {
            el.style.cursor = 'grab';
        });
    });
});