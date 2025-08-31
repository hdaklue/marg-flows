/**
 * Calendar Utilities for KluePortal Calendar Component
 * Provides date calculations, formatting, and utility functions
 */

export class CalendarUtils {
    /**
     * Get the number of days in a month
     */
    static getDaysInMonth(year, month) {
        return new Date(year, month + 1, 0).getDate();
    }

    /**
     * Get the first day of the week for a given month
     */
    static getFirstDayOfMonth(year, month) {
        return new Date(year, month, 1).getDay();
    }

    /**
     * Get the start and end dates for a calendar month view
     */
    static getMonthViewDates(year, month) {
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        
        // Get the start of the week for the first day
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - startDate.getDay());
        
        // Get the end of the week for the last day
        const endDate = new Date(lastDay);
        endDate.setDate(endDate.getDate() + (6 - lastDay.getDay()));
        
        return { startDate, endDate, firstDay, lastDay };
    }

    /**
     * Get week view dates
     */
    static getWeekViewDates(date) {
        const startDate = new Date(date);
        startDate.setDate(startDate.getDate() - startDate.getDay());
        
        const endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + 6);
        
        return { startDate, endDate };
    }

    /**
     * Check if two dates are the same day
     */
    static isSameDay(date1, date2) {
        return date1.getFullYear() === date2.getFullYear() &&
               date1.getMonth() === date2.getMonth() &&
               date1.getDate() === date2.getDate();
    }

    /**
     * Check if a date is today
     */
    static isToday(date) {
        return this.isSameDay(date, new Date());
    }

    /**
     * Check if a date is in the current month
     */
    static isCurrentMonth(date, referenceDate) {
        return date.getFullYear() === referenceDate.getFullYear() &&
               date.getMonth() === referenceDate.getMonth();
    }

    /**
     * Check if a date is a weekend
     */
    static isWeekend(date) {
        const dayOfWeek = date.getDay();
        return dayOfWeek === 0 || dayOfWeek === 6; // Sunday or Saturday
    }

    /**
     * Format date for display
     */
    static formatDate(date, format = 'short') {
        const options = {
            short: { month: 'short', day: 'numeric', year: 'numeric' },
            long: { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' },
            monthYear: { month: 'long', year: 'numeric' },
            dayMonth: { month: 'short', day: 'numeric' },
            time: { hour: 'numeric', minute: '2-digit' },
            datetime: { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }
        };

        return date.toLocaleDateString('en-US', options[format] || options.short);
    }

    /**
     * Calculate event duration
     */
    static calculateDuration(startDate, endDate) {
        if (!startDate || !endDate) return null;
        
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffMs = end - start;
        
        const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
        
        if (days > 0) {
            return `${days}d ${hours}h ${minutes}m`;
        } else if (hours > 0) {
            return `${hours}h ${minutes}m`;
        } else {
            return `${minutes}m`;
        }
    }

    /**
     * Generate calendar grid for month view
     */
    static generateMonthGrid(year, month, events = []) {
        const { startDate, endDate, firstDay, lastDay } = this.getMonthViewDates(year, month);
        const weeks = [];
        let currentDate = new Date(startDate);
        
        while (currentDate <= endDate) {
            const week = [];
            
            for (let i = 0; i < 7; i++) {
                const dayEvents = events.filter(event => {
                    const eventDate = new Date(event.startDate);
                    return this.isSameDay(eventDate, currentDate);
                });
                
                week.push({
                    date: new Date(currentDate),
                    day: currentDate.getDate(),
                    isCurrentMonth: this.isCurrentMonth(currentDate, firstDay),
                    isToday: this.isToday(currentDate),
                    isWeekend: this.isWeekend(currentDate),
                    events: dayEvents,
                    dayOfWeek: currentDate.getDay()
                });
                
                currentDate.setDate(currentDate.getDate() + 1);
            }
            
            weeks.push(week);
        }
        
        return weeks;
    }

    /**
     * Generate calendar data for week view
     */
    static generateWeekGrid(date, events = []) {
        const { startDate, endDate } = this.getWeekViewDates(date);
        const days = [];
        let currentDate = new Date(startDate);
        
        for (let i = 0; i < 7; i++) {
            const dayEvents = events.filter(event => {
                const eventDate = new Date(event.startDate);
                return this.isSameDay(eventDate, currentDate);
            });
            
            days.push({
                date: new Date(currentDate),
                day: currentDate.getDate(),
                dayName: currentDate.toLocaleDateString('en-US', { weekday: 'short' }),
                isToday: this.isToday(currentDate),
                isWeekend: this.isWeekend(currentDate),
                events: dayEvents
            });
            
            currentDate.setDate(currentDate.getDate() + 1);
        }
        
        return days;
    }

    /**
     * Get timezone offset for a given timezone
     */
    static getTimezoneOffset(timezone) {
        if (!timezone) return 0;
        
        try {
            const now = new Date();
            const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
            const targetTime = new Date(utc + (this.getTimezoneOffsetHours(timezone) * 3600000));
            return targetTime.getTimezoneOffset();
        } catch (error) {
            console.warn('Invalid timezone:', timezone);
            return 0;
        }
    }

    /**
     * Get timezone offset in hours
     */
    static getTimezoneOffsetHours(timezone) {
        const timezoneOffsets = {
            'UTC': 0,
            'EST': -5,
            'CST': -6,
            'MST': -7,
            'PST': -8,
            'GMT': 0,
            // Add more as needed
        };
        
        return timezoneOffsets[timezone] || 0;
    }

    /**
     * Check if a date falls within restrictions
     */
    static isDateAllowed(date, restrictions = {}) {
        const { minDate, maxDate, disabledDates = [] } = restrictions;
        
        // Check minimum date
        if (minDate && date < new Date(minDate)) {
            return false;
        }
        
        // Check maximum date
        if (maxDate && date > new Date(maxDate)) {
            return false;
        }
        
        // Check disabled dates
        if (disabledDates.some(disabledDate => this.isSameDay(date, new Date(disabledDate)))) {
            return false;
        }
        
        return true;
    }

    /**
     * Get event color class based on color value
     */
    static getEventColorClass(color) {
        if (!color) return 'bg-sky-500 text-white';
        
        // If it's a hex color, return empty (will use inline style)
        if (color.startsWith('#')) {
            return '';
        }
        
        // If it's a Tailwind color
        if (color.includes('-')) {
            return `bg-${color} text-white`;
        }
        
        // Default colors mapping
        const colorMap = {
            'blue': 'bg-blue-500 text-white',
            'green': 'bg-emerald-500 text-white',
            'red': 'bg-red-500 text-white',
            'yellow': 'bg-amber-500 text-white',
            'purple': 'bg-purple-500 text-white',
            'pink': 'bg-pink-500 text-white',
            'gray': 'bg-zinc-500 text-white',
            'indigo': 'bg-indigo-500 text-white',
        };
        
        return colorMap[color] || 'bg-sky-500 text-white';
    }

    /**
     * Get inline style for custom colors
     */
    static getEventInlineStyle(color) {
        if (!color || !color.startsWith('#')) {
            return '';
        }
        
        return `background-color: ${color}; color: white;`;
    }

    /**
     * Debounce function for performance optimization
     */
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle function for performance optimization
     */
    static throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Parse date string safely
     */
    static parseDate(dateString) {
        try {
            const date = new Date(dateString);
            return isNaN(date.getTime()) ? null : date;
        } catch (error) {
            return null;
        }
    }

    /**
     * Get readable date range string
     */
    static getDateRangeString(startDate, endDate) {
        if (!startDate) return '';
        
        const start = new Date(startDate);
        
        if (!endDate) {
            return this.formatDate(start, 'short');
        }
        
        const end = new Date(endDate);
        
        if (this.isSameDay(start, end)) {
            return this.formatDate(start, 'short');
        }
        
        if (start.getFullYear() === end.getFullYear()) {
            if (start.getMonth() === end.getMonth()) {
                return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${end.getDate()}, ${end.getFullYear()}`;
            } else {
                return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}, ${end.getFullYear()}`;
            }
        }
        
        return `${this.formatDate(start, 'short')} - ${this.formatDate(end, 'short')}`;
    }
}