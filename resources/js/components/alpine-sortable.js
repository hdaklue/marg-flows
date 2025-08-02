import Sortable from 'sortablejs';
import Hammer from 'hammerjs';

// Device detection utility
const isTouchDevice = () => 'ontouchstart' in window || navigator.maxTouchPoints > 0;

// Use WeakMap for automatic garbage collection when elements are removed
const sortableInstances = new WeakMap();
const sortableHandlers = new Map();
const hammerInstances = new WeakMap();

// Modal management
let modalInstance = null;

// Create and show column picker modal
function showColumnPickerModal({ taskId, taskTitle, currentColumn, availableColumns }) {
    // Remove existing modal if any
    if (modalInstance) {
        modalInstance.remove();
        modalInstance = null;
    }

    // Create modal HTML
    const modalHTML = `
        <div id="column-picker-modal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50" style="animation: fadeIn 0.3s ease;">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl max-w-sm w-full mx-4 p-6" style="animation: slideUp 0.3s ease;">
                <!-- Modal Header -->
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Move Task</h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">${taskTitle}</p>
                </div>
                
                <!-- Column Options -->
                <div class="space-y-2 mb-6">
                    ${availableColumns.map(column => `
                        <button data-column-id="${column.id}" 
                            class="column-option w-full text-left p-3 rounded-lg border border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3 ${getColumnColorClass(column.color)}"></div>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">${column.name}</span>
                        </button>
                    `).join('')}
                </div>
                
                <!-- Cancel Button -->
                <button id="modal-cancel" class="w-full py-2 px-4 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                    Cancel
                </button>
            </div>
        </div>
        <style>
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideUp {
                from { transform: translateY(20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
        </style>
    `;

    // Create modal element
    modalInstance = document.createElement('div');
    modalInstance.innerHTML = modalHTML;
    document.body.appendChild(modalInstance);

    // Add event listeners
    const modal = document.getElementById('column-picker-modal');
    
    // Close on backdrop click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Close on cancel
    document.getElementById('modal-cancel').addEventListener('click', closeModal);

    // Handle column selection
    modal.querySelectorAll('.column-option').forEach(button => {
        button.addEventListener('click', () => {
            const targetColumn = button.dataset.columnId;
            
            // Dispatch sortable:sort event
            window.dispatchEvent(new CustomEvent('sortable:sort', {
                detail: {
                    action: 'move',
                    item: taskId,
                    from: currentColumn,
                    to: targetColumn,
                    timestamp: Date.now()
                }
            }));

            closeModal();
        });
    });

    // Close on escape key
    document.addEventListener('keydown', handleEscapeKey);
}

function closeModal() {
    if (modalInstance) {
        modalInstance.style.animation = 'fadeOut 0.2s ease';
        setTimeout(() => {
            if (modalInstance) {
                modalInstance.remove();
                modalInstance = null;
            }
        }, 200);
    }
    document.removeEventListener('keydown', handleEscapeKey);
}

function handleEscapeKey(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
}

function getColumnColorClass(color) {
    const colorMap = {
        'zinc': 'bg-zinc-500',
        'amber': 'bg-amber-500',
        'emerald': 'bg-emerald-500'
    };
    return colorMap[color] || 'bg-zinc-500';
}

// Touch device detection utility

document.addEventListener('alpine:init', () => {
    // x-sortable directive with on-sort and on-drag handlers
    Alpine.directive('sortable', (el, { expression }, { evaluate, cleanup }) => {
        // Get handlers from attributes
        const onSortHandler = el.getAttribute('on-sort');
        const onDragHandler = el.getAttribute('on-drag');

        const groupName = expression || el.getAttribute('x-sortable') || 'shared';

        // Store handlers with proper cleanup
        sortableHandlers.set(groupName, {
            onSort: onSortHandler ? (data) => evaluate(onSortHandler, { $event: { detail: data } }) : null,
            onDrag: onDragHandler ? (data) => evaluate(onDragHandler, { $event: { detail: data } }) : null
        });

        cleanup(() => {
            sortableHandlers.delete(groupName);
        });
    });

    // x-sortable-group creates new Sortable instances
    Alpine.directive('sortable-group', (el, { expression }) => {
        // Cache group name lookup for performance
        const groupName = el.closest('[x-sortable]')?.getAttribute('x-sortable') || 'shared';

        // Clean up existing instance if it exists
        const existingInstance = sortableInstances.get(el);
        if (existingInstance) {
            existingInstance.destroy();
            sortableInstances.delete(el);
        }

        // Create new Sortable instance with conditional touch handling
        const sortableConfig = {
            group: groupName, // Use group name from parent x-sortable
            animation: 200,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            // Handle only on touch devices, entire item draggable on desktop
            ...(isTouchDevice() && { handle: '.sortable-handle' }),
            
            // Performance optimizations
            preventOnFilter: false,
            filter: '.no-drag', // Allow filtering out non-draggable elements
            scrollSensitivity: 40,
            scrollSpeed: 15,
            bubbleScroll: true,
            
            // Conditional configuration based on device type
            ...(isTouchDevice() ? {
                // Touch devices: minimize native touch handling to avoid conflicts with Hammer.js
                delay: 0, // No delay since Hammer handles touch timing
                delayOnTouchStart: false, // Disable touch delay
                touchStartThreshold: 999, // Effectively disable native touch start detection
                forceFallback: true, // Force fallback to mouse events only
                fallbackTolerance: 0, // Strict fallback
                disabled: false, // Keep enabled but rely on fallback
            } : {
                // Desktop devices: use native drag optimizations
                touchStartThreshold: 10,
                forceFallback: false,
                fallbackTolerance: 5,
                delayOnTouchStart: false,
                delay: 0,
            })
        };

        // Add event handlers to the config
        sortableConfig.onStart = function (evt) {
                // Add haptic feedback for mobile devices
                if (navigator.vibrate) {
                    navigator.vibrate(50);
                }
                
                // Add visual feedback class to item
                evt.item.classList.add('dragging');
                
                const dragData = {
                    item: evt.item.getAttribute('x-sortable:item') || evt.item.dataset.sortableItem,
                    from: evt.from.dataset.container,
                    oldIndex: evt.oldIndex
                };

                // Removed custom onDrag handler - using events only

                // Dispatch global onDrag event
                window.dispatchEvent(new CustomEvent('sortable:drag', {
                    detail: dragData
                }));
                
                // Prevent page scrolling during drag on mobile (only if not touch device with Hammer active)
                if (!isTouchDevice()) {
                    document.body.style.overflow = 'hidden';
                }
            };

        sortableConfig.onEnd = function (evt) {
                // Remove visual feedback classes
                evt.item.classList.remove('dragging');
                
                // Restore page scrolling
                document.body.style.overflow = '';
                
                // Add success haptic feedback for successful drops
                if (navigator.vibrate && evt.from !== evt.to) {
                    navigator.vibrate([25, 25, 25]); // Success pattern
                }
                
                // Cache item identifier for reuse
                const itemId = evt.item.getAttribute('x-sortable:item') || evt.item.dataset.sortableItem;
                
                // Get sorted item IDs from direct children only (optimized)
                const items = Array.from(evt.to.children, child => 
                    child.getAttribute('x-sortable:item') || child.dataset.sortableItem
                ).filter(Boolean);

                const sortData = {
                    items,
                    from: evt.from.dataset.container,
                    to: evt.to.dataset.container,
                    oldIndex: evt.oldIndex,
                    newIndex: evt.newIndex,
                    item: itemId,
                    // Add mobile-specific data
                    wasCrossColumn: evt.from !== evt.to,
                    timestamp: Date.now()
                };

                // Removed custom onSort handler - using events only

                // Dispatch global onSort event
                window.dispatchEvent(new CustomEvent('sortable:sort', { 
                    detail: { payload: sortData } 
                }));

                // Dispatch to Livewire if available with throttling for performance
                if (window.Livewire?.dispatch) {
                    // Clear any pending dispatches
                    if (this._livewireTimeout) {
                        clearTimeout(this._livewireTimeout);
                    }
                    
                    // Throttle Livewire dispatches to prevent overwhelming the server
                    this._livewireTimeout = setTimeout(() => {
                        window.Livewire.dispatch('sortable:sort', sortData);
                    }, 100);
                }
            };

        const sortable = new Sortable(el, sortableConfig);

        // Store instance using WeakMap for automatic cleanup
        sortableInstances.set(el, sortable);

        // Clean up on element removal
        return () => {
            if (sortable) {
                sortable.destroy();
                sortableInstances.delete(el);
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

    // x-sortable:handle marks elements as drag handles with device-specific optimization
    Alpine.directive('sortable:handle', (el) => {
        el.classList.add('sortable-handle');
        el.style.cursor = 'grab';
        el.setAttribute('aria-label', 'Drag handle - touch and hold to drag');
        
        const isTouch = isTouchDevice();
        let eventListeners = [];
        let longPressTimer = null;

        // Common handle state functions
        const handleStart = (e) => {
            el.style.cursor = 'grabbing';
            el.classList.add('handle-active');
            
            // Add haptic feedback for touch start
            if (e.type === 'touchstart' && navigator.vibrate) {
                navigator.vibrate(10);
            }
        };

        const handleEnd = () => {
            el.style.cursor = 'grab';
            el.classList.remove('handle-active');
        };

        const handleCancel = () => {
            el.style.cursor = 'grab';
            el.classList.remove('handle-active');
        };
        
        const handleLongPress = () => {
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }
            el.classList.add('handle-long-press');
        };
        
        const clearLongPress = () => {
            if (longPressTimer) {
                clearTimeout(longPressTimer);
                longPressTimer = null;
            }
            el.classList.remove('handle-long-press');
        };

        // Helper to track event listeners for cleanup
        const addEventListener = (event, handler, options = { passive: true }) => {
            el.addEventListener(event, handler, options);
            eventListeners.push({ event, handler });
        };

        if (isTouch) {
            // Touch device: Only register touch events
            el.style.touchAction = 'manipulation';
            
            addEventListener('touchstart', (e) => {
                handleStart(e);
                longPressTimer = setTimeout(handleLongPress, 300);
            });
            
            addEventListener('touchend', () => {
                handleEnd();
                clearLongPress();
            });
            
            addEventListener('touchcancel', () => {
                handleCancel();
                clearLongPress();
            });
            
            addEventListener('touchmove', clearLongPress);
        } else {
            // Non-touch device: Only register mouse events
            addEventListener('mousedown', handleStart);
            addEventListener('mouseup', handleEnd);
            addEventListener('mouseleave', handleCancel);
        }

        // Return cleanup function
        return () => {
            // Clean up event listeners
            eventListeners.forEach(({ event, handler }) => {
                el.removeEventListener(event, handler);
            });
            eventListeners = [];
            
            // Clear any pending timers
            if (longPressTimer) {
                clearTimeout(longPressTimer);
                longPressTimer = null;
            }
        };
    });

    // x-swipe directive using Hammer.js for touch devices only
    Alpine.directive('swipe', (el, { expression }, { evaluate, cleanup }) => {
        // Only initialize on touch devices to avoid conflicts
        if (!isTouchDevice()) {
            return () => {}; // Return empty cleanup function
        }

        // Parse swipe configuration from expression
        const config = evaluate(expression) || {};
        const {
            threshold = 80,  // Even lower threshold for completion
            velocity = 0.1,  // Much lower velocity requirement
            onPanStart,
            onPanMove,
            onPanEnd,
            currentColumn = 'unknown',
            taskId,
            taskTitle,
            availableColumns = []
        } = config;

        // Clean up existing Hammer instance
        const existingHammer = hammerInstances.get(el);
        if (existingHammer) {
            existingHammer.destroy();
            hammerInstances.delete(el);
        }

        // Create new Hammer instance with mobile-optimized settings
        const hammer = new Hammer(el, {
            touchAction: 'pan-y', // Allow vertical scrolling, detect horizontal pans
            recognizers: [
                // Configure pan recognizer for swipe detection
                [Hammer.Pan, {
                    direction: Hammer.DIRECTION_HORIZONTAL,
                    threshold: 5, // Lower threshold to start recognizing sooner
                    pointers: 1 // Single finger only
                }],
                // Configure swipe recognizer
                [Hammer.Swipe, {
                    direction: Hammer.DIRECTION_HORIZONTAL,
                    velocity: velocity,
                    threshold: threshold,
                    pointers: 1
                }]
            ]
        });

        // Store current pan state
        let panState = {
            startX: 0,
            currentX: 0,
            distance: 0,
            isActive: false
        };

        // Handle pan start
        hammer.on('panstart', (e) => {
            console.log('PANSTART:', e.direction, Hammer.DIRECTION_LEFT, Hammer.DIRECTION_RIGHT);
            if (e.direction === Hammer.DIRECTION_LEFT || e.direction === Hammer.DIRECTION_RIGHT) {
                panState.isActive = true;
                panState.startX = e.center.x;
                panState.currentX = e.center.x;
                panState.distance = 0;
                console.log('PAN STARTED - State:', panState);

                // Haptic feedback on start
                if (navigator.vibrate) {
                    navigator.vibrate(10);
                }

                // Call custom pan start handler
                if (onPanStart) {
                    evaluate(onPanStart, { $event: { detail: { ...panState, originalEvent: e } } });
                }

                // Dispatch pan start event
                el.dispatchEvent(new CustomEvent('swipe:start', {
                    detail: { ...panState, originalEvent: e }
                }));
            }
        });

        // Handle pan move for visual feedback
        hammer.on('panmove', (e) => {
            if (panState.isActive) {
                panState.currentX = e.center.x;
                panState.distance = e.deltaX;

                // Dispatch pan move event for visual feedback first
                el.dispatchEvent(new CustomEvent('swipe:move', {
                    detail: { ...panState, originalEvent: e }
                }));

                // Call custom pan move handler after dispatching event
                if (onPanMove) {
                    try {
                        evaluate(onPanMove);
                    } catch (error) {
                        console.error('Error in onPanMove handler:', error);
                    }
                }
            }
        });

        // Handle pan end
        hammer.on('panend', (e) => {
            if (panState.isActive) {
                panState.isActive = false;

                // Check if pan distance is sufficient for action (fallback for failed swipe detection)
                const absDistance = Math.abs(panState.distance);
                console.log('PANEND - Distance:', panState.distance, 'Abs:', absDistance, 'Threshold:', threshold);
                if (absDistance > threshold) {
                    console.log('THRESHOLD EXCEEDED - Triggering fallback action');
                    // Pan in any direction - show column picker modal
                    console.log('FALLBACK: Pan detected - showing column picker modal');
                    if (navigator.vibrate) navigator.vibrate([50, 50, 50]);
                    
                    // Filter available columns to exclude current column
                    const filteredColumns = availableColumns.filter(col => col.id !== currentColumn);
                    
                    // Show pure JS modal
                    showColumnPickerModal({
                        taskId,
                        taskTitle,
                        currentColumn,
                        availableColumns: filteredColumns
                    });
                }

                // Dispatch pan end event
                el.dispatchEvent(new CustomEvent('swipe:end', {
                    detail: { ...panState, originalEvent: e }
                }));

                // Call custom pan end handler after dispatching event
                if (onPanEnd) {
                    try {
                        evaluate(onPanEnd);
                    } catch (error) {
                        console.error('Error in onPanEnd handler:', error);
                    }
                }

                // Reset pan state
                panState = { startX: 0, currentX: 0, distance: 0, isActive: false };
            }
        });

        // Handle swipe in any direction - show column picker modal
        hammer.on('swiperight swipeleft', (e) => {
            console.log('SWIPE DETECTED!', { taskId, taskTitle, currentColumn });
            if (navigator.vibrate) {
                navigator.vibrate([50, 50, 50]);
            }

            // Filter available columns to exclude current column
            const filteredColumns = availableColumns.filter(col => col.id !== currentColumn);

            // Show pure JS modal directly
            showColumnPickerModal({
                taskId,
                taskTitle,
                currentColumn,
                availableColumns: filteredColumns
            });
        });

        // Store Hammer instance for cleanup
        hammerInstances.set(el, hammer);

        // Cleanup function
        cleanup(() => {
            if (hammer) {
                hammer.destroy();
                hammerInstances.delete(el);
            }
        });

        // Return cleanup for directive
        return () => {
            if (hammer) {
                hammer.destroy();
                hammerInstances.delete(el);
            }
        };
    });
});