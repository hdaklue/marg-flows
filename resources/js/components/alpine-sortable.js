import Hammer from 'hammerjs';
import Sortable from 'sortablejs';

// Device detection utility
const isTouchDevice = () => 'ontouchstart' in window || navigator.maxTouchPoints > 0;

// Use WeakMap for automatic garbage collection when elements are removed
const sortableInstances = new WeakMap();
const sortableHandlers = new Map();
const hammerInstances = new WeakMap();

// Desktop auto-scroll configuration
const desktopScrollConfig = {
    edgeSize: 120, // Base edge zone size
    edgeSizeMultiplier: 0.15, // 15% of container width for responsive zones
    minScrollSpeed: 5, // Minimum speed at edge
    maxScrollSpeed: 50, // Maximum speed at center of zone
};

// Simplified scroll state for desktop only
let scrollState = {
    isDragging: false,
    draggedElement: null,
    container: null,
    snapToContainer: null,
};

// Modal management
let modalInstance = null;

// Desktop drag management with Draggabilly
let desktopDragInstance = null;
let activeDragInstances = new WeakMap();
let dragIndicator = null;

// Desktop scroll handling with containment logic
function handleDesktopScroll(element, container) {
    if (isTouchDevice() || !scrollState.snapToContainer) return; // Skip on touch devices
    
    const elementRect = element.getBoundingClientRect();
    const containerRect = container.getBoundingClientRect();
    const snapToRect = scrollState.snapToContainer.getBoundingClientRect();
    const elementCenterX = elementRect.left + elementRect.width / 2;
    
    // Check if element is near the edges of the snap-to container
    const leftDistance = elementCenterX - snapToRect.left;
    const rightDistance = snapToRect.right - elementCenterX;
    
    // Scroll when element approaches containment boundaries
    if (leftDistance < desktopScrollConfig.edgeSize) {
        container.scrollLeft = Math.max(0, container.scrollLeft - desktopScrollConfig.scrollSpeed);
    } else if (rightDistance < desktopScrollConfig.edgeSize) {
        const maxScroll = container.scrollWidth - container.clientWidth;
        container.scrollLeft = Math.min(maxScroll, container.scrollLeft + desktopScrollConfig.scrollSpeed);
    }
}

// Placeholder for Draggabilly integration
// This will be replaced with Draggabilly-based desktop scrolling

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
        'blue': 'bg-blue-500',
        'purple': 'bg-purple-500',
        'emerald': 'bg-emerald-500',
        'red': 'bg-red-500'
    };
    return colorMap[color] || 'bg-zinc-500';
}

// Removed complex element pinning and trapping systems
// These will be replaced with simpler Draggabilly-based scrolling for desktop

// Removed complex trigger zones system
// Will be replaced with Draggabilly containment-based scrolling

// Removed getNextGroupTarget - will use Draggabilly containment instead

// Drag indicator functions for desktop Draggabilly integration
function createDragIndicator(originalElement) {
    console.log('🎯 Creating drag indicator for:', originalElement);
    
    // Clean up any existing indicator
    removeDragIndicator();
    
    // Clone the original element for the indicator
    dragIndicator = originalElement.cloneNode(true);
    dragIndicator.id = 'drag-indicator';
    dragIndicator.style.cssText = `
        position: fixed;
        pointer-events: none;
        z-index: 10000;
        opacity: 0.8;
        transform: scale(1.05);
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        transition: none;
    `;
    
    // Add to body
    document.body.appendChild(dragIndicator);
    console.log('📍 Drag indicator added to DOM');
    
    // Create Draggabilly instance on the indicator
    const snapToContainer = document.getElementById('snap-to');
    if (snapToContainer && window.Draggabilly) {
        const draggie = new window.Draggabilly(dragIndicator, {
            containment: snapToContainer
        });
        
        console.log('✅ Draggabilly instance created with containment');
        
        // Handle drag events for scrolling
        draggie.on('dragStart', function() {
            console.log('🚀 Draggabilly dragStart event fired');
        });
        
        draggie.on('dragMove', function(event, pointer, moveVector) {
            console.log('🔄 Drag indicator moving:', moveVector);
            const container = document.querySelector('.scrollbar-hide');
            if (container) {
                handleDesktopScroll(dragIndicator, container);
            }
        });
        
        draggie.on('dragEnd', function() {
            console.log('🏁 Draggabilly dragEnd event fired');
        });
        
        // Store instance for cleanup
        activeDragInstances.set(dragIndicator, draggie);
    } else {
        console.log('❌ Missing:', { snapToContainer, Draggabilly: !!window.Draggabilly });
    }
}

function updateDragIndicatorPosition(draggedRect) {
    if (dragIndicator && draggedRect) {
        dragIndicator.style.left = draggedRect.left + 'px';
        dragIndicator.style.top = draggedRect.top + 'px';
        dragIndicator.style.width = draggedRect.width + 'px';
        dragIndicator.style.height = draggedRect.height + 'px';
    }
}

function removeDragIndicator() {
    if (dragIndicator) {
        // Clean up Draggabilly instance
        const draggie = activeDragInstances.get(dragIndicator);
        if (draggie) {
            draggie.destroy();
            activeDragInstances.delete(dragIndicator);
        }
        
        // Remove from DOM
        dragIndicator.remove();
        dragIndicator = null;
    }
}

// Removed complex auto-scroll element checking and boundary initialization
// Will be replaced with Draggabilly containment system

document.addEventListener('alpine:init', () => {
    // Initialize Alpine sortable directives (no static test needed anymore)

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
    Alpine.directive('sortable-group', (el, { expression }, { evaluate }) => {
        // Cache group name lookup for performance
        const groupName = el.closest('[x-sortable]')?.getAttribute('x-sortable') || 'shared';

        // Parse configuration from expression to determine sorting behavior
        let allowWithinGroupSort = false; // Disabled by default

        if (expression) {
            try {
                // Handle simple string patterns first
                if (expression === 'sort' || expression === 'sort:true') {
                    allowWithinGroupSort = true;
                } else if (expression.includes('sort')) {
                    allowWithinGroupSort = true;
                } else if (expression.startsWith('{') || expression.includes('sort')) {
                    // Try to evaluate as Alpine expression or object
                    const config = evaluate(expression) || {};
                    if (typeof config === 'object') {
                        // Handle object configuration: {sort: true}, {allowSort: true}, {sortWithinGroup: true}
                        if (config.sort === true || config.allowSort === true || config.sortWithinGroup === true) {
                            allowWithinGroupSort = true;
                        }
                    } else if (typeof config === 'boolean') {
                        // Handle direct boolean evaluation: sort: true
                        allowWithinGroupSort = config;
                    }
                }
            } catch (e) {
                // If evaluation fails, fall back to string pattern matching
                if (expression.includes('sort') && !expression.includes('sort:false') && !expression.includes('false')) {
                    allowWithinGroupSort = true;
                }
            }
        }

        // Also check for data attributes as alternative configuration methods
        if (el.hasAttribute('data-allow-sort') && el.getAttribute('data-allow-sort') === 'true') {
            allowWithinGroupSort = true;
        }
        if (el.hasAttribute('data-sort') && el.getAttribute('data-sort') === 'true') {
            allowWithinGroupSort = true;
        }

        // Clean up existing instance if it exists
        const existingInstance = sortableInstances.get(el);
        if (existingInstance) {
            existingInstance.destroy();
            sortableInstances.delete(el);
        }

        // Create new Sortable instance with conditional touch handling
        const sortableConfig = {
            group: groupName, // Use group name from parent x-sortable
            sort: allowWithinGroupSort, // Control within-group sorting
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

        // Modern SortableJS event handling
        
        // Element is chosen (selected for dragging)
        sortableConfig.onChoose = function (evt) {
            // Nothing needed here
        };
        
        // Element dragging started
        sortableConfig.onStart = function (evt) {
            scrollState.container = document.querySelector('.scrollbar-hide');
            
            // Add visual feedback
            evt.item.classList.add('dragging');
            
            // Make original element semi-transparent during drag on desktop
            if (!isTouchDevice()) {
                evt.item.style.opacity = '0.3';
                
                // Drag styling is now handled by CSS in the Blade template
                
                // Disable scroll snap during drag for smooth scrolling
                if (scrollState.container) {
                    scrollState.originalScrollBehavior = scrollState.container.style.scrollBehavior;
                    scrollState.originalScrollSnapType = scrollState.container.style.scrollSnapType;
                    scrollState.container.style.scrollBehavior = 'auto';
                    scrollState.container.style.scrollSnapType = 'none';
                    console.log('🚫 Disabled scroll snap for smooth dragging');
                }
                
                // Position the drag indicator at the element's location
                if (dragIndicator) {
                    const rect = evt.item.getBoundingClientRect();
                    dragIndicator.style.left = rect.left + 'px';
                    dragIndicator.style.top = rect.top + 'px';
                    dragIndicator.style.width = rect.width + 'px';
                    dragIndicator.style.height = rect.height + 'px';
                }
            }
            
            // Prevent page scrolling during drag on desktop
            if (!isTouchDevice()) {
                document.body.style.overflow = 'hidden';
            }
            
            // Dispatch drag start event
            const dragData = {
                item: evt.item.getAttribute('x-sortable:item') || evt.item.dataset.sortableItem,
                from: evt.from.dataset.container,
                oldIndex: evt.oldIndex
            };
            
            window.dispatchEvent(new CustomEvent('sortable:drag', {
                detail: dragData
            }));
        };

        // Handle responsive scrolling with variable speed
        let scrollContainer = null;
        let scrollAnimationFrame = null;
        let lastDraggedRect = null;
        
        function performScroll() {
            if (!scrollContainer || !lastDraggedRect) return;
            
            // CRITICAL: Recalculate bounds on every frame as container scrolls
            const scrollBounds = scrollContainer.getBoundingClientRect();
            const maxScroll = scrollContainer.scrollWidth - scrollContainer.clientWidth;
            
            // Calculate responsive edge zones based on container width
            const containerWidth = scrollBounds.width;
            const responsiveEdgeSize = Math.max(
                desktopScrollConfig.edgeSize, 
                containerWidth * desktopScrollConfig.edgeSizeMultiplier
            );
            
            // Calculate scroll speed based on position in edge zones
            let scrollSpeed = 0;
            
            // Enhanced left zone detection with responsive sizing
            const leftZoneStart = scrollBounds.left;
            const leftZoneEnd = scrollBounds.left + (responsiveEdgeSize * 1.5);
            
            // Right zone detection with responsive sizing
            const rightZoneStart = scrollBounds.right - responsiveEdgeSize;
            
            // Check both zones independently - prioritize the stronger signal
            let leftScrollSpeed = 0;
            let rightScrollSpeed = 0;
            
            if (lastDraggedRect.left - 50 < leftZoneEnd) {
                // Calculate distance into left zone using left edge - 50px (triggers before entering)
                const distanceIntoZone = leftZoneEnd - (lastDraggedRect.left - 50);
                const zoneProgress = Math.min(1, distanceIntoZone / (responsiveEdgeSize * 1.5));
                leftScrollSpeed = -(desktopScrollConfig.minScrollSpeed + (zoneProgress * (desktopScrollConfig.maxScrollSpeed - desktopScrollConfig.minScrollSpeed)));
            }
            
            if (lastDraggedRect.right + 120 > rightZoneStart) {
                const distanceIntoZone = (lastDraggedRect.right + 120) - rightZoneStart;
                const zoneProgress = Math.min(1, distanceIntoZone / responsiveEdgeSize);
                rightScrollSpeed = desktopScrollConfig.minScrollSpeed + (zoneProgress * (desktopScrollConfig.maxScrollSpeed - desktopScrollConfig.minScrollSpeed));
            }
            
            // Use the stronger scroll signal (allows for smooth direction changes)
            if (Math.abs(leftScrollSpeed) > Math.abs(rightScrollSpeed)) {
                scrollSpeed = leftScrollSpeed;
            } else if (rightScrollSpeed !== 0) {
                scrollSpeed = rightScrollSpeed;
            }
            
            // Apply scroll if needed
            if (scrollSpeed !== 0) {
                const currentScroll = scrollContainer.scrollLeft;
                const newScroll = Math.max(0, Math.min(maxScroll, currentScroll + scrollSpeed));
                
                if (newScroll !== currentScroll) {
                    scrollContainer.scrollLeft = newScroll;
                }
                
                // Continue scrolling
                scrollAnimationFrame = requestAnimationFrame(performScroll);
            } else {
                stopScrolling();
            }
        }
        
        function startScrolling() {
            if (!scrollAnimationFrame) {
                scrollAnimationFrame = requestAnimationFrame(performScroll);
            }
        }
        
        function stopScrolling() {
            if (scrollAnimationFrame) {
                cancelAnimationFrame(scrollAnimationFrame);
                scrollAnimationFrame = null;
            }
        }
        
        sortableConfig.onMove = function(evt, originalEvent) {
            if (!isTouchDevice() && evt.draggedRect) {
                // Cache container reference only (bounds calculated per frame)
                if (!scrollContainer) {
                    scrollContainer = document.getElementById('scrollable') || 
                                    el.closest('[x-sortable]') || 
                                    document.querySelector('[x-sortable]');
                }
                
                // Store current dragged rect for animation
                lastDraggedRect = evt.draggedRect;
                
                // Start scrolling animation if not already running
                startScrolling();
            }
            return true;
        };
        
        // Note: Cleanup is now handled directly in sortableConfig.onEnd above

        // Element is unchosen (deselected)
        sortableConfig.onUnchoose = function(evt) {
            // Nothing needed here
        };
        // Element dragging ended
        sortableConfig.onEnd = function (evt) {
            
            // CRITICAL: Stop all scrolling and clean up drag state first
            stopScrolling();
            // Reset ALL scroll-related state for fresh start next time
            scrollContainer = null;
            lastDraggedRect = null;
            
            // Reset scroll state
            scrollState.isDragging = false;
            scrollState.draggedElement = null;
            scrollState.container = null;
            scrollState.snapToContainer = null;
            
            // Remove visual feedback classes and restore opacity
            evt.item.classList.remove('dragging', 'sortable-chosen', 'sortable-drag');
            if (!isTouchDevice()) {
                evt.item.style.opacity = '';
                evt.item.style.transform = '';
                evt.item.style.zIndex = '';
                
                // Restore scroll snap after drag
                if (scrollState.container) {
                    scrollState.container.style.scrollBehavior = scrollState.originalScrollBehavior || '';
                    scrollState.container.style.scrollSnapType = scrollState.originalScrollSnapType || '';
                    console.log('✅ Restored scroll snap after dragging');
                }
            }
            
            // Restore page scrolling
            document.body.style.overflow = '';
            
            // Clean up any drag indicators
            removeDragIndicator();
            
            // Clean up any active drag instances
            if (activeDragInstances.size > 0) {
                activeDragInstances.forEach((draggie, element) => {
                    if (draggie && draggie.destroy) {
                        draggie.destroy();
                    }
                });
                activeDragInstances.clear();
            }
            
            // Check if there was an actual change (position change or cross-group move)
            const actualChange = evt.from !== evt.to || evt.oldIndex !== evt.newIndex;
            
            // Only proceed if there was an actual change
            if (!actualChange) {
                return; // No change occurred, don't dispatch events
            }
            
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
            return () => { }; // Return empty cleanup function
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
            if (e.direction === Hammer.DIRECTION_LEFT || e.direction === Hammer.DIRECTION_RIGHT) {
                panState.isActive = true;
                panState.startX = e.center.x;
                panState.currentX = e.center.x;
                panState.distance = 0;

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
                if (absDistance > threshold) {
                    // Pan in any direction - show column picker modal
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
