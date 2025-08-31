/**
 * Calendar Interactions for KluePortal Calendar Component
 * Handles keyboard navigation, touch gestures, and accessibility
 */

import { CalendarUtils } from './calendar-utils.js';

export class CalendarInteractions {
    constructor(calendarElement, calendarInstance) {
        this.element = calendarElement;
        this.calendar = calendarInstance;
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.isSwipeGesture = false;
        
        this.init();
    }

    init() {
        this.setupKeyboardNavigation();
        this.setupTouchGestures();
        this.setupAccessibility();
        this.setupDragAndDrop();
    }

    /**
     * Setup keyboard navigation
     */
    setupKeyboardNavigation() {
        this.element.addEventListener('keydown', this.handleKeyDown.bind(this));
        
        // Make calendar focusable
        if (!this.element.hasAttribute('tabindex')) {
            this.element.setAttribute('tabindex', '0');
        }
    }

    /**
     * Handle keyboard events
     */
    handleKeyDown(event) {
        // Don't interfere with form inputs
        if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
            return;
        }

        const { key, shiftKey, ctrlKey, metaKey } = event;
        
        switch (key) {
            case 'ArrowLeft':
                event.preventDefault();
                this.navigateDate(-1);
                break;
                
            case 'ArrowRight':
                event.preventDefault();
                this.navigateDate(1);
                break;
                
            case 'ArrowUp':
                event.preventDefault();
                this.navigateDate(-7); // Previous week
                break;
                
            case 'ArrowDown':
                event.preventDefault();
                this.navigateDate(7); // Next week
                break;
                
            case 'Home':
                event.preventDefault();
                if (ctrlKey || metaKey) {
                    this.calendar.goToToday();
                } else {
                    this.goToStartOfPeriod();
                }
                break;
                
            case 'End':
                event.preventDefault();
                this.goToEndOfPeriod();
                break;
                
            case 'PageUp':
                event.preventDefault();
                if (shiftKey) {
                    this.navigatePeriod(-12); // Previous year
                } else {
                    this.navigatePeriod(-1); // Previous month/week
                }
                break;
                
            case 'PageDown':
                event.preventDefault();
                if (shiftKey) {
                    this.navigatePeriod(12); // Next year
                } else {
                    this.navigatePeriod(1); // Next month/week
                }
                break;
                
            case 'Enter':
            case ' ':
                event.preventDefault();
                this.activateSelectedDate();
                break;
                
            case 't':
            case 'T':
                if (!shiftKey && !ctrlKey && !metaKey) {
                    event.preventDefault();
                    this.calendar.goToToday();
                }
                break;
                
            case 'm':
            case 'M':
                if (!shiftKey && !ctrlKey && !metaKey) {
                    event.preventDefault();
                    this.calendar.changeView('month');
                }
                break;
                
            case 'w':
            case 'W':
                if (!shiftKey && !ctrlKey && !metaKey) {
                    event.preventDefault();
                    this.calendar.changeView('week');
                }
                break;
                
            case 'd':
            case 'D':
                if (!shiftKey && !ctrlKey && !metaKey) {
                    event.preventDefault();
                    this.calendar.changeView('day');
                }
                break;
        }
    }

    /**
     * Navigate by days
     */
    navigateDate(days) {
        const currentDate = new Date(this.calendar.currentDate);
        currentDate.setDate(currentDate.getDate() + days);
        this.calendar.goToDate(currentDate.toISOString());
    }

    /**
     * Navigate by periods (months/weeks)
     */
    navigatePeriod(periods) {
        const currentView = this.calendar.currentView;
        
        if (currentView === 'month') {
            const newDate = new Date(this.calendar.currentDate);
            newDate.setMonth(newDate.getMonth() + periods);
            this.calendar.goToDate(newDate.toISOString());
        } else if (currentView === 'week') {
            const newDate = new Date(this.calendar.currentDate);
            newDate.setDate(newDate.getDate() + (periods * 7));
            this.calendar.goToDate(newDate.toISOString());
        } else {
            this.navigateDate(periods);
        }
    }

    /**
     * Go to start of current period
     */
    goToStartOfPeriod() {
        const currentDate = new Date(this.calendar.currentDate);
        const currentView = this.calendar.currentView;
        
        if (currentView === 'month') {
            currentDate.setDate(1);
        } else if (currentView === 'week') {
            currentDate.setDate(currentDate.getDate() - currentDate.getDay());
        }
        
        this.calendar.goToDate(currentDate.toISOString());
    }

    /**
     * Go to end of current period
     */
    goToEndOfPeriod() {
        const currentDate = new Date(this.calendar.currentDate);
        const currentView = this.calendar.currentView;
        
        if (currentView === 'month') {
            currentDate.setMonth(currentDate.getMonth() + 1, 0);
        } else if (currentView === 'week') {
            currentDate.setDate(currentDate.getDate() + (6 - currentDate.getDay()));
        }
        
        this.calendar.goToDate(currentDate.toISOString());
    }

    /**
     * Activate selected date (trigger date selection)
     */
    activateSelectedDate() {
        const currentDate = this.calendar.currentDate;
        this.calendar.selectDate(currentDate.toISOString());
    }

    /**
     * Setup touch gestures for mobile
     */
    setupTouchGestures() {
        this.element.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: false });
        this.element.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        this.element.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: false });
    }

    /**
     * Handle touch start
     */
    handleTouchStart(event) {
        if (event.touches.length === 1) {
            this.touchStartX = event.touches[0].clientX;
            this.touchStartY = event.touches[0].clientY;
            this.isSwipeGesture = false;
        }
    }

    /**
     * Handle touch move
     */
    handleTouchMove(event) {
        if (event.touches.length === 1) {
            const deltaX = Math.abs(event.touches[0].clientX - this.touchStartX);
            const deltaY = Math.abs(event.touches[0].clientY - this.touchStartY);
            
            // Determine if this is a horizontal swipe gesture
            if (deltaX > 30 && deltaX > deltaY * 2) {
                this.isSwipeGesture = true;
                event.preventDefault(); // Prevent scrolling
            }
        }
    }

    /**
     * Handle touch end
     */
    handleTouchEnd(event) {
        if (this.isSwipeGesture && event.changedTouches.length === 1) {
            const deltaX = event.changedTouches[0].clientX - this.touchStartX;
            const threshold = 100;
            
            if (Math.abs(deltaX) > threshold) {
                if (deltaX > 0) {
                    // Swipe right - go to previous
                    this.calendar.goToPrevious();
                } else {
                    // Swipe left - go to next
                    this.calendar.goToNext();
                }
            }
        }
        
        this.isSwipeGesture = false;
    }

    /**
     * Setup accessibility features
     */
    setupAccessibility() {
        // Add ARIA labels
        this.element.setAttribute('role', 'application');
        this.element.setAttribute('aria-label', 'Calendar navigation');
        
        // Add live region for announcements
        this.createLiveRegion();
        
        // Update ARIA labels when calendar changes
        this.calendar.$wire.on('calendar:view-changed', this.updateAriaLabels.bind(this));
        this.calendar.$wire.on('calendar:date-changed', this.updateAriaLabels.bind(this));
    }

    /**
     * Create live region for screen reader announcements
     */
    createLiveRegion() {
        if (!document.getElementById('calendar-live-region')) {
            const liveRegion = document.createElement('div');
            liveRegion.id = 'calendar-live-region';
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.className = 'sr-only';
            document.body.appendChild(liveRegion);
        }
    }

    /**
     * Update ARIA labels and announce changes
     */
    updateAriaLabels() {
        const liveRegion = document.getElementById('calendar-live-region');
        const currentView = this.calendar.currentView;
        const currentDate = this.calendar.currentDateFormatted;
        
        if (liveRegion) {
            liveRegion.textContent = `Calendar ${currentView} view, ${currentDate}`;
        }
        
        // Update calendar description
        this.element.setAttribute('aria-label', `Calendar ${currentView} view, ${currentDate}`);
    }

    /**
     * Setup drag and drop for events (if enabled)
     */
    setupDragAndDrop() {
        // This would be implemented if drag-and-drop functionality is needed
        // For now, we'll just set up the basic structure
        
        this.element.addEventListener('dragover', this.handleDragOver.bind(this));
        this.element.addEventListener('drop', this.handleDrop.bind(this));
    }

    /**
     * Handle drag over
     */
    handleDragOver(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    }

    /**
     * Handle drop
     */
    handleDrop(event) {
        event.preventDefault();
        
        // Get the target date
        const targetElement = event.target.closest('[data-date]');
        if (targetElement) {
            const targetDate = targetElement.dataset.date;
            const eventData = event.dataTransfer.getData('application/json');
            
            if (eventData) {
                try {
                    const eventObj = JSON.parse(eventData);
                    // Emit event for parent to handle
                    this.calendar.$wire.$dispatch('calendar:event-dropped', {
                        event: eventObj,
                        newDate: targetDate
                    });
                } catch (error) {
                    console.warn('Invalid event data for drop:', error);
                }
            }
        }
    }

    /**
     * Setup event delegation for dynamic content
     */
    setupEventDelegation() {
        this.element.addEventListener('click', this.handleClick.bind(this));
        this.element.addEventListener('dblclick', this.handleDoubleClick.bind(this));
    }

    /**
     * Handle clicks within calendar
     */
    handleClick(event) {
        const eventElement = event.target.closest('[data-event-id]');
        const dateElement = event.target.closest('[data-date]');
        
        if (eventElement) {
            const eventId = eventElement.dataset.eventId;
            this.calendar.selectEvent(eventId);
        } else if (dateElement) {
            const date = dateElement.dataset.date;
            this.calendar.selectDate(date);
        }
    }

    /**
     * Handle double clicks
     */
    handleDoubleClick(event) {
        const dateElement = event.target.closest('[data-date]');
        
        if (dateElement) {
            const date = dateElement.dataset.date;
            // Emit event for creating new event
            this.calendar.$wire.$dispatch('calendar:date-double-clicked', {
                date: date
            });
        }
    }

    /**
     * Cleanup event listeners
     */
    destroy() {
        this.element.removeEventListener('keydown', this.handleKeyDown.bind(this));
        this.element.removeEventListener('touchstart', this.handleTouchStart.bind(this));
        this.element.removeEventListener('touchmove', this.handleTouchMove.bind(this));
        this.element.removeEventListener('touchend', this.handleTouchEnd.bind(this));
        this.element.removeEventListener('dragover', this.handleDragOver.bind(this));
        this.element.removeEventListener('drop', this.handleDrop.bind(this));
        this.element.removeEventListener('click', this.handleClick.bind(this));
        this.element.removeEventListener('dblclick', this.handleDoubleClick.bind(this));
        
        // Remove live region if no other calendars exist
        const liveRegion = document.getElementById('calendar-live-region');
        if (liveRegion && document.querySelectorAll('[role="application"]').length === 1) {
            liveRegion.remove();
        }
    }
}