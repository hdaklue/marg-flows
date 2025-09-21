/**
 * ObjectiveBlock Plugin for EditorJS
 * Allows creation of campaign/strategy objectives with name, operator, and percentage
 */

class ObjectiveBlock {
    static get toolbox() {
        return {
            title: 'Objective',
            icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>`
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    static get enableLineBreaks() {
        return true;
    }

    static get tunes() {
        return ['commentTune'];
    }

    constructor({ data, config, api, readOnly, block }) {
        this.api = api;
        this.blockAPI = block;
        this.readOnly = readOnly;
        this.config = config || {};
        this.data = data || {};

        // Initialize localization
        this.t = this.initializeLocalization();

        // Initialize default data structure
        this.data = {
            name: this.data.name || '',
            operator: this.data.operator || 'increase',
            percentage: this.data.percentage || 0,
            unit: this.data.unit || 'percentage',
            ...this.data
        };

        // Initialize wrapper reference
        this.wrapper = null;
        this.modal = null;
        this.isEditing = false;
    }

    initializeLocalization() {
        // Detect current locale from HTML lang attribute or other sources
        const htmlElement = document.documentElement;
        const currentLocale = htmlElement.lang || 'en';
        const locale = currentLocale.split('-')[0]; // Get base locale (e.g., 'en' from 'en-US')
        
        // Define translations for ObjectiveBlock plugin
        const translations = {
            'en': {
                namePlaceholder: 'Enter objective name...',
                operatorIncrease: 'Increase',
                operatorDecrease: 'Decrease', 
                operatorEqual: 'Equal',
                percentageLabel: 'Percentage',
                percentagePlaceholder: '0',
                invalidPercentage: 'Please enter a valid percentage (0-100)',
                emptyObjective: 'Please enter an objective name',
                placeholderTitle: 'Set Your Objective',
                placeholderSubtitle: 'Click to define campaign goals and targets',
                addObjective: 'Add Objective',
                editObjective: 'Edit Objective',
                objectiveLabel: 'Objective',
                target: 'Target',
                edit: 'Edit',
                close: 'Close',
                objectiveName: 'Objective Name',
                goalType: 'Goal Type',
                targetPercentage: 'Target Percentage',
                preview: 'Preview',
                cancel: 'Cancel',
                save: 'Save Objective',
                previewSample: 'Sample Objective',
                increaseDesc: 'Grow by target value',
                decreaseDesc: 'Reduce by target value',
                equalDesc: 'Maintain target value'
            },
            'ar': {
                namePlaceholder: 'أدخل اسم الهدف...',
                operatorIncrease: 'زيادة',
                operatorDecrease: 'نقص',
                operatorEqual: 'مساوي',
                percentageLabel: 'النسبة المئوية',
                percentagePlaceholder: '0',
                invalidPercentage: 'يرجى إدخال نسبة مئوية صحيحة (0-100)',
                emptyObjective: 'يرجى إدخال اسم الهدف',
                placeholderTitle: 'حدد هدفك',
                placeholderSubtitle: 'انقر لتحديد أهداف ومستهدفات الحملة',
                addObjective: 'إضافة هدف',
                editObjective: 'تعديل الهدف',
                objectiveLabel: 'الهدف',
                target: 'المستهدف',
                edit: 'تعديل',
                close: 'إغلاق',
                objectiveName: 'اسم الهدف',
                goalType: 'نوع الهدف',
                targetPercentage: 'النسبة المستهدفة',
                preview: 'معاينة',
                cancel: 'إلغاء',
                save: 'حفظ الهدف',
                previewSample: 'هدف تجريبي',
                increaseDesc: 'زيادة بالقيمة المستهدفة',
                decreaseDesc: 'تقليل بالقيمة المستهدفة',
                equalDesc: 'الحفاظ على القيمة المستهدفة'
            }
        };
        
        return translations[locale] || translations['en'];
    }

    render() {
        const wrapper = document.createElement('div');
        wrapper.classList.add('objective-block');

        // Store wrapper reference for later use
        this.wrapper = wrapper;

        // Check if we have existing data to display
        if (this.isEmpty() && !this.readOnly) {
            // Show placeholder for new blocks
            const placeholder = this.createPlaceholder();
            wrapper.appendChild(placeholder);
        } else {
            // Show the objective display
            const display = this.createObjectiveDisplay();
            wrapper.appendChild(display);
        }

        return wrapper;
    }

    isDarkMode() {
        return document.documentElement.classList.contains('dark');
    }

    createPlaceholder() {
        const placeholder = document.createElement('div');
        placeholder.classList.add('objective-block__placeholder');
        
        placeholder.innerHTML = `
            <div style="
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 16px;
                background: ${this.isDarkMode() ? '#27272a' : '#f4f4f5'};
                border: 1px dashed ${this.isDarkMode() ? '#52525b' : '#d4d4d8'};
                border-radius: 8px;
                color: ${this.isDarkMode() ? '#a1a1aa' : '#71717a'};
                cursor: pointer;
                transition: all 0.2s;
                margin-bottom: 16px;
            " onmouseover="this.style.borderColor='${this.isDarkMode() ? '#71717a' : '#a1a1aa'}'" onmouseout="this.style.borderColor='${this.isDarkMode() ? '#52525b' : '#d4d4d8'}'">
                <div style="
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 24px;
                    height: 24px;
                    background: ${this.isDarkMode() ? '#3f3f46' : '#e4e4e7'};
                    border-radius: 4px;
                    color: ${this.isDarkMode() ? '#a1a1aa' : '#71717a'};
                    flex-shrink: 0;
                ">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div style="flex: 1; font-size: 14px; font-weight: 500;">
                    ${this.t.placeholderTitle || 'Set Your Objective'} 
                    <span style="font-weight: 400; opacity: 0.8;">• ${this.t.placeholderSubtitle || 'Click to define campaign goals and targets'}</span>
                </div>
                <div style="
                    font-size: 12px;
                    font-weight: 500;
                    color: ${this.isDarkMode() ? '#3b82f6' : '#2563eb'};
                    background: ${this.isDarkMode() ? '#1e3a8a' : '#eff6ff'};
                    padding: 4px 8px;
                    border-radius: 4px;
                    flex-shrink: 0;
                ">
                    ${this.t.addObjective || 'Add Objective'}
                </div>
            </div>
        `;
        
        // Add click handler to open modal
        if (!this.readOnly) {
            placeholder.addEventListener('click', () => {
                this.openModal();
            });
        }
        
        return placeholder;
    }

    createObjectiveDisplay() {
        const display = document.createElement('div');
        display.classList.add('objective-block__display-container');
        
        // Detect dark mode
        const isDarkMode = this.isDarkMode();
        
        // Create the translated objective text
        const translatedText = this.getTranslatedObjectiveText();
        
        display.innerHTML = `
            <div class="objective-block__display" style="
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 12px;
                margin-bottom: 16px;
                background: ${isDarkMode ? '#27272a' : '#f4f4f5'};
                border: 1px solid ${isDarkMode ? '#3f3f46' : '#e4e4e7'};
                border-radius: 6px;
                color: ${isDarkMode ? '#fafafa' : '#18181b'};
                font-weight: 500;
                font-size: 14px;
                line-height: 1.3;
            ">
                <div style="
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 20px;
                    height: 20px;
                    background: ${isDarkMode ? '#52525b' : '#d4d4d8'};
                    border-radius: 4px;
                    color: ${isDarkMode ? '#fafafa' : '#18181b'};
                    flex-shrink: 0;
                ">
                    ${this.getOperatorIcon(this.data.operator)}
                </div>
                <div style="flex: 1; overflow: hidden;">
                    ${translatedText}
                </div>
            </div>
        `;
        
        return display;
    }

    getTranslatedObjectiveText() {
        // Format the number with commas
        const formattedNumber = this.formatNumber(this.data.percentage);
        const unit = (this.data.unit || 'percentage') === 'percentage' ? '%' : '';
        const value = formattedNumber + unit;
        
        // Detect current locale
        const htmlElement = document.documentElement;
        const currentLocale = htmlElement.lang || 'en';
        const locale = currentLocale.split('-')[0]; // Get base locale (e.g., 'en' from 'en-US')
        
        // Create translated text based on operator and language
        switch (this.data.operator) {
            case 'increase':
                return locale === 'ar' 
                    ? `زيادة ${this.data.name} بنسبة ${value}`
                    : `Increase ${this.data.name} by ${value}`;
            case 'decrease':
                return locale === 'ar'
                    ? `تقليل ${this.data.name} بنسبة ${value}`
                    : `Decrease ${this.data.name} by ${value}`;
            case 'equal':
                return locale === 'ar'
                    ? `الحفاظ على ${this.data.name} عند ${value}`
                    : `Maintain ${this.data.name} at ${value}`;
            default:
                return `${this.data.name}: ${value}`;
        }
    }

    formatNumber(num) {
        // Convert to number if it's a string
        const number = parseFloat(num) || 0;
        
        // Use toLocaleString for proper number formatting with commas
        return number.toLocaleString('en-US', {
            maximumFractionDigits: 2,
            minimumFractionDigits: 0
        });
    }

    getOperatorIcon(operator) {
        switch (operator) {
            case 'increase':
                return `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5-5 5 5"/>
                </svg>`;
            case 'decrease':
                return `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5"/>
                </svg>`;
            case 'equal':
                return `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 9h14M5 15h14"/>
                </svg>`;
            default:
                return '';
        }
    }

    openModal() {
        if (this.isEditing) return;
        
        this.isEditing = true;
        this.createModal();
    }

    createModal() {
        // Detect dark mode
        const isDarkMode = this.isDarkMode();
        
        // Create modal backdrop
        const backdrop = document.createElement('div');
        backdrop.setAttribute('data-modal', 'objective-block');
        backdrop.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        `;
        
        // Create modal container
        const modal = document.createElement('div');
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-labelledby', 'objective-modal-title');
        modal.setAttribute('aria-modal', 'true');
        modal.style.cssText = `
            background: ${isDarkMode ? '#18181b' : '#ffffff'};
            color: ${isDarkMode ? '#fafafa' : '#18181b'};
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, ${isDarkMode ? '0.3' : '0.1'}), 0 10px 10px -5px rgba(0, 0, 0, ${isDarkMode ? '0.2' : '0.04'});
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        `;
        
        // Store modal reference
        this.modal = backdrop;
        
        // Create modal content
        modal.innerHTML = this.createModalContent(isDarkMode);
        backdrop.appendChild(modal);
        
        // Add to DOM
        document.body.appendChild(backdrop);
        
        // Initialize modal functionality
        this.initializeModal(backdrop, modal);
        
        // Focus management
        setTimeout(() => {
            const nameInput = modal.querySelector('.objective-block__modal-name-input');
            if (nameInput) {
                nameInput.focus();
            }
        }, 100);
    }

    createModalContent(isDarkMode = false) {
        const isRTL = document.documentElement.dir === 'rtl';
        
        const colors = {
            border: isDarkMode ? '#3f3f46' : '#e4e4e7',
            text: isDarkMode ? '#fafafa' : '#18181b',
            textSecondary: isDarkMode ? '#d4d4d8' : '#3f3f46',
            textMuted: isDarkMode ? '#a1a1aa' : '#71717a',
            input: isDarkMode ? '#27272a' : '#ffffff',
            inputBorder: isDarkMode ? '#52525b' : '#d4d4d8',
            inputFocus: isDarkMode ? '#3b82f6' : '#3b82f6',
            background: isDarkMode ? '#18181b' : '#ffffff',
            backgroundSecondary: isDarkMode ? '#09090b' : '#f4f4f5',
            backgroundHover: isDarkMode ? '#27272a' : '#f4f4f5'
        };
        
        return `
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 24px 24px 0 24px; border-bottom: 1px solid ${colors.border};">
                <h2 id="objective-modal-title" style="margin: 0; font-size: 18px; font-weight: 600; color: ${colors.text};">
                    ${this.isEmpty() ? (this.t.addObjective || 'Add Objective') : (this.t.editObjective || 'Edit Objective')}
                </h2>
                <button type="button" style="
                    background: none; 
                    border: none; 
                    padding: 8px; 
                    cursor: pointer; 
                    border-radius: 6px; 
                    color: ${colors.textMuted};
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: background-color 0.2s;
                " aria-label="${this.t.close || 'Close'}" onmouseover="this.style.backgroundColor='${colors.backgroundHover}'" onmouseout="this.style.backgroundColor='transparent'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div style="padding: 24px;">
                <div style="display: flex; flex-direction: column; gap: 24px;">
                    <!-- Objective Name -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="objective-name" style="font-size: 14px; font-weight: 500; color: ${colors.textSecondary};">
                            ${this.t.objectiveName || 'Objective Name'}
                        </label>
                        <div style="position: relative;">
                            <input 
                                type="text" 
                                id="objective-name"
                                class="objective-block__modal-name-input"
                                placeholder="${this.t.namePlaceholder || 'Enter objective name...'}"
                                value="${this.data.name || ''}"
                                autocomplete="off"
                                style="
                                    width: 100%;
                                    padding: 12px;
                                    border: 1px solid ${colors.inputBorder};
                                    border-radius: 8px;
                                    font-size: 14px;
                                    color: ${colors.text};
                                    background: ${colors.input};
                                    transition: border-color 0.2s;
                                    box-sizing: border-box;
                                "
                                onfocus="this.style.borderColor='${colors.inputFocus}'; this.style.outline='none'"
                                onblur="this.style.borderColor='${colors.inputBorder}'"
                            />
                            <div class="objective-block__modal-dropdown" style="
                                position: absolute;
                                top: 100%;
                                left: 0;
                                right: 0;
                                background: ${colors.input};
                                border: 1px solid ${colors.inputBorder};
                                border-top: none;
                                border-radius: 0 0 8px 8px;
                                max-height: 200px;
                                overflow-y: auto;
                                z-index: 100;
                                display: none;
                                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                            "></div>
                        </div>
                        <div class="objective-block__modal-field-error" style="display: none; font-size: 12px; color: #ef4444; margin-top: 4px;"></div>
                    </div>
                    
                    <!-- Goal Type -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="font-size: 14px; font-weight: 500; color: ${colors.textSecondary};">
                            ${this.t.goalType || 'Goal Type'}
                        </label>
                        <div style="display: grid; grid-template-columns: 1fr; gap: 8px;">
                            <div class="objective-block__modal-operator-option" data-operator="increase" style="
                                display: flex;
                                align-items: center;
                                gap: 8px;
                                padding: 12px;
                                border: 1px solid ${this.data.operator === 'increase' ? '#3b82f6' : colors.border};
                                border-radius: 6px;
                                cursor: pointer;
                                transition: all 0.2s;
                                background: ${this.data.operator === 'increase' ? (isDarkMode ? '#1e3a8a' : '#eff6ff') : colors.background};
                            " onmouseover="if(!this.dataset.selected) this.style.borderColor='#93c5fd'" onmouseout="if(!this.dataset.selected) this.style.borderColor='${colors.border}'">
                                <div style="
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    width: 32px;
                                    height: 32px;
                                    border-radius: 6px;
                                    background: ${this.data.operator === 'increase' ? (isDarkMode ? '#1e40af' : '#dbeafe') : (isDarkMode ? '#52525b' : '#f4f4f5')};
                                    color: ${this.data.operator === 'increase' ? '#059669' : colors.textMuted};
                                ">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5-5 5 5"/>
                                    </svg>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: ${colors.text}; margin-bottom: 4px;">${this.t.operatorIncrease || 'Increase'}</div>
                                    <div style="font-size: 14px; color: ${colors.textMuted};">${this.t.increaseDesc || 'Grow by target percentage'}</div>
                                </div>
                            </div>
                            
                            <div class="objective-block__modal-operator-option" data-operator="decrease" style="
                                display: flex;
                                align-items: center;
                                gap: 8px;
                                padding: 12px;
                                border: 1px solid ${this.data.operator === 'decrease' ? '#3b82f6' : colors.border};
                                border-radius: 6px;
                                cursor: pointer;
                                transition: all 0.2s;
                                background: ${this.data.operator === 'decrease' ? (isDarkMode ? '#1e3a8a' : '#eff6ff') : colors.background};
                            " onmouseover="if(!this.dataset.selected) this.style.borderColor='#93c5fd'" onmouseout="if(!this.dataset.selected) this.style.borderColor='${colors.border}'">
                                <div style="
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    width: 32px;
                                    height: 32px;
                                    border-radius: 6px;
                                    background: ${this.data.operator === 'decrease' ? (isDarkMode ? '#1e40af' : '#dbeafe') : (isDarkMode ? '#52525b' : '#f4f4f5')};
                                    color: ${this.data.operator === 'decrease' ? '#2563eb' : colors.textMuted};
                                ">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5"/>
                                    </svg>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: ${colors.text}; margin-bottom: 4px;">${this.t.operatorDecrease || 'Decrease'}</div>
                                    <div style="font-size: 14px; color: ${colors.textMuted};">${this.t.decreaseDesc || 'Reduce by target percentage'}</div>
                                </div>
                            </div>
                            
                            <div class="objective-block__modal-operator-option" data-operator="equal" style="
                                display: flex;
                                align-items: center;
                                gap: 8px;
                                padding: 12px;
                                border: 1px solid ${this.data.operator === 'equal' ? '#3b82f6' : colors.border};
                                border-radius: 6px;
                                cursor: pointer;
                                transition: all 0.2s;
                                background: ${this.data.operator === 'equal' ? (isDarkMode ? '#1e3a8a' : '#eff6ff') : colors.background};
                            " onmouseover="if(!this.dataset.selected) this.style.borderColor='#93c5fd'" onmouseout="if(!this.dataset.selected) this.style.borderColor='${colors.border}'">
                                <div style="
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    width: 32px;
                                    height: 32px;
                                    border-radius: 6px;
                                    background: ${this.data.operator === 'equal' ? (isDarkMode ? '#1e40af' : '#dbeafe') : (isDarkMode ? '#52525b' : '#f4f4f5')};
                                    color: ${this.data.operator === 'equal' ? '#2563eb' : colors.textMuted};
                                ">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 9h14M5 15h14"/>
                                    </svg>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: ${colors.text}; margin-bottom: 4px;">${this.t.operatorEqual || 'Equal'}</div>
                                    <div style="font-size: 14px; color: ${colors.textMuted};">${this.t.equalDesc || 'Maintain target percentage'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Target Value -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <label for="objective-percentage" style="font-size: 14px; font-weight: 500; color: ${colors.textSecondary};">
                                ${this.t.targetValue || 'Target Value'}
                            </label>
                            <div style="display: flex; gap: 4px; background: ${colors.backgroundSecondary}; border-radius: 6px; padding: 2px;">
                                <button type="button" class="objective-block__modal-unit-toggle" data-unit="percentage" style="
                                    padding: 6px 12px;
                                    font-size: 12px;
                                    font-weight: 500;
                                    border: none;
                                    border-radius: 4px;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                    background: ${(this.data.unit || 'percentage') === 'percentage' ? '#3b82f6' : 'transparent'};
                                    color: ${(this.data.unit || 'percentage') === 'percentage' ? 'white' : colors.textMuted};
                                ">%</button>
                                <button type="button" class="objective-block__modal-unit-toggle" data-unit="number" style="
                                    padding: 6px 12px;
                                    font-size: 12px;
                                    font-weight: 500;
                                    border: none;
                                    border-radius: 4px;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                    background: ${(this.data.unit || 'percentage') === 'number' ? '#3b82f6' : 'transparent'};
                                    color: ${(this.data.unit || 'percentage') === 'number' ? 'white' : colors.textMuted};
                                ">#</button>
                            </div>
                        </div>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input 
                                type="number" 
                                id="objective-percentage"
                                class="objective-block__modal-percentage-input"
                                min="0" 
                                step="0.1"
                                value="${this.data.percentage || 0}"
                                style="
                                    width: 100%;
                                    padding: 12px 40px 12px 12px;
                                    border: 1px solid ${colors.inputBorder};
                                    border-radius: 8px;
                                    font-size: 14px;
                                    color: ${colors.text};
                                    background: ${colors.input};
                                    transition: border-color 0.2s;
                                    box-sizing: border-box;
                                "
                                onfocus="this.style.borderColor='${colors.inputFocus}'; this.style.outline='none'"
                                onblur="this.style.borderColor='${colors.inputBorder}'"
                            />
                            <span class="objective-block__modal-unit-symbol" style="
                                position: absolute;
                                right: 12px;
                                font-size: 14px;
                                color: ${colors.textMuted};
                                pointer-events: none;
                            ">${(this.data.unit || 'percentage') === 'percentage' ? '%' : '#'}</span>
                        </div>
                        <div class="objective-block__modal-field-error" style="display: none; font-size: 12px; color: #ef4444; margin-top: 4px;"></div>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 24px; border-top: 1px solid ${colors.border};">
                <button type="button" data-action="cancel" style="
                    padding: 12px 24px;
                    border: 1px solid ${colors.inputBorder};
                    border-radius: 8px;
                    background: ${colors.background};
                    color: ${colors.textSecondary};
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s;
                " onmouseover="this.style.backgroundColor='${colors.backgroundHover}'" onmouseout="this.style.backgroundColor='${colors.background}'">
                    ${this.t.cancel || 'Cancel'}
                </button>
                <button type="button" data-action="save" style="
                    padding: 12px 24px;
                    border: none;
                    border-radius: 8px;
                    background: #3b82f6;
                    color: white;
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s;
                " onmouseover="this.style.backgroundColor='#2563eb'" onmouseout="this.style.backgroundColor='#3b82f6'">
                    ${this.t.save || 'Save Objective'}
                </button>
            </div>
        `;
    }


    initializeModal(backdrop, modal) {
        const nameInput = modal.querySelector('.objective-block__modal-name-input');
        const percentageInput = modal.querySelector('.objective-block__modal-percentage-input');
        const unitToggleButtons = modal.querySelectorAll('.objective-block__modal-unit-toggle');
        const unitSymbol = modal.querySelector('.objective-block__modal-unit-symbol');
        const operatorOptions = modal.querySelectorAll('.objective-block__modal-operator-option');
        const saveButton = modal.querySelector('[data-action="save"]');
        const cancelButton = modal.querySelector('[data-action="cancel"]');
        const closeButton = modal.querySelector('[aria-label*="Close"]');
        const dropdown = modal.querySelector('.objective-block__modal-dropdown');
        
        // Temporary data for form
        let tempData = { ...this.data };
        
        // Ensure we have a unit value
        if (!tempData.unit) {
            tempData.unit = 'percentage';
        }
        
        // Get predefined objectives from config
        const predefinedObjectives = this.config.predefinedObjectives || [];
        
        // Initialize dropdown functionality
        this.initializeDropdown(nameInput, dropdown, predefinedObjectives, tempData);
        
        // Update unit symbol and input constraints
        const updateUnitDisplay = (unit) => {
            if (unitSymbol) {
                unitSymbol.textContent = unit === 'percentage' ? '%' : '#';
            }
            if (percentageInput) {
                if (unit === 'percentage') {
                    percentageInput.max = '100';
                    percentageInput.step = '0.1';
                } else {
                    percentageInput.removeAttribute('max');
                    percentageInput.step = '1';
                }
            }
        };
        
        // Name input handler (updated to work with dropdown)
        if (nameInput) {
            nameInput.addEventListener('input', (e) => {
                tempData.name = e.target.value;
                this.validateField(nameInput, tempData.name);
                this.filterDropdownOptions(dropdown, e.target.value, predefinedObjectives);
            });
        }
        
        // Percentage/number input handler
        if (percentageInput) {
            percentageInput.addEventListener('input', (e) => {
                const value = parseFloat(e.target.value) || 0;
                tempData.percentage = value;
                this.validateField(percentageInput, value);
            });
        }
        
        // Unit toggle handlers
        unitToggleButtons.forEach(button => {
            button.addEventListener('click', () => {
                const selectedUnit = button.dataset.unit;
                tempData.unit = selectedUnit;
                
                // Update button styles
                unitToggleButtons.forEach(btn => {
                    if (btn.dataset.unit === selectedUnit) {
                        btn.style.background = '#3b82f6';
                        btn.style.color = 'white';
                    } else {
                        btn.style.background = 'transparent';
                        btn.style.color = '#6b7280';
                    }
                });
                
                updateUnitDisplay(selectedUnit);
            });
        });
        
        // Initialize unit display
        updateUnitDisplay(tempData.unit);
        
        // Detect dark mode for operator styling
        const isDarkMode = this.isDarkMode();
        
        const colors = {
            border: isDarkMode ? '#3f3f46' : '#e4e4e7',
            background: isDarkMode ? '#18181b' : '#ffffff'
        };
        
        // Operator selection
        operatorOptions.forEach(option => {
            option.addEventListener('click', () => {
                // Remove active styling from all
                operatorOptions.forEach(opt => {
                    opt.dataset.selected = 'false';
                    // Reset styling to default (non-selected) state
                    opt.style.borderColor = colors.border;
                    opt.style.background = colors.background;
                });
                
                // Add active styling to clicked
                option.dataset.selected = 'true';
                const operatorType = option.dataset.operator;
                // All operators use the same blue styling when selected
                option.style.borderColor = '#3b82f6';
                option.style.background = isDarkMode ? '#1e3a8a' : '#eff6ff';
                
                tempData.operator = option.dataset.operator;
            });
        });
        
        // Save button
        saveButton.addEventListener('click', () => {
            // Ensure percentage is a number
            tempData.percentage = parseFloat(tempData.percentage) || 0;
            
            console.log('Saving objective data:', tempData);
            
            if (this.validateForm(tempData)) {
                this.data = { ...tempData };
                console.log('Objective saved successfully:', this.data);
                this.closeModal();
                this.updateBlockDisplay();
                this.validateAndNotify();
            } else {
                console.log('Validation failed for objective data:', tempData);
            }
        });
        
        // Cancel and close handlers
        const closeModal = () => this.closeModal();
        cancelButton.addEventListener('click', closeModal);
        closeButton.addEventListener('click', closeModal);
        
        // Backdrop click to close
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                closeModal();
            }
        });
        
        // Escape key to close
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
        
        // Modal animation
        requestAnimationFrame(() => {
            backdrop.classList.add('active');
        });
    }
    
    initializeDropdown(nameInput, dropdown, predefinedObjectives, tempData) {
        if (!nameInput || !dropdown || !predefinedObjectives.length) return;
        
        const isDarkMode = this.isDarkMode();
        const colors = {
            text: isDarkMode ? '#fafafa' : '#18181b',
            textMuted: isDarkMode ? '#a1a1aa' : '#71717a',
            backgroundHover: isDarkMode ? '#27272a' : '#f4f4f5'
        };
        
        // Show dropdown on focus
        nameInput.addEventListener('focus', () => {
            this.showDropdown(dropdown, predefinedObjectives, colors, nameInput, tempData);
        });
        
        // Hide dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!nameInput.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
        
        // Hide dropdown on escape
        nameInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                dropdown.style.display = 'none';
                nameInput.blur();
            }
        });
    }
    
    showDropdown(dropdown, objectives, colors, nameInput, tempData) {
        dropdown.innerHTML = '';
        
        objectives.forEach((objective, index) => {
            const option = document.createElement('div');
            option.style.cssText = `
                padding: 12px;
                cursor: pointer;
                color: ${colors.text};
                font-size: 14px;
                transition: background-color 0.2s;
                border-bottom: 1px solid ${colors.backgroundHover};
            `;
            
            option.textContent = objective;
            
            // Add hover effect
            option.addEventListener('mouseenter', () => {
                option.style.backgroundColor = colors.backgroundHover;
            });
            
            option.addEventListener('mouseleave', () => {
                option.style.backgroundColor = 'transparent';
            });
            
            // Add click handler
            option.addEventListener('click', () => {
                nameInput.value = objective;
                tempData.name = objective;
                dropdown.style.display = 'none';
                this.validateField(nameInput, tempData.name);
            });
            
            dropdown.appendChild(option);
        });
        
        dropdown.style.display = 'block';
    }
    
    filterDropdownOptions(dropdown, inputValue, predefinedObjectives) {
        if (!dropdown || !predefinedObjectives.length) return;
        
        const isDarkMode = this.isDarkMode();
        const colors = {
            text: isDarkMode ? '#fafafa' : '#18181b',
            textMuted: isDarkMode ? '#a1a1aa' : '#71717a',
            backgroundHover: isDarkMode ? '#27272a' : '#f4f4f5'
        };
        
        // Trim input and check if it's empty or only whitespace
        const trimmedInput = inputValue.trim();
        
        // If input is empty or only whitespace, show all options without highlighting
        if (!trimmedInput) {
            this.showDropdown(dropdown, predefinedObjectives, colors, null, null);
            return;
        }
        
        // Filter objectives based on input
        const filteredObjectives = predefinedObjectives.filter(objective => 
            objective.toLowerCase().includes(trimmedInput.toLowerCase())
        );
        
        // Clear dropdown
        dropdown.innerHTML = '';
        
        if (filteredObjectives.length === 0) {
            dropdown.style.display = 'none';
            return;
        }
        
        // Add filtered options
        filteredObjectives.forEach((objective) => {
            const option = document.createElement('div');
            option.style.cssText = `
                padding: 12px;
                cursor: pointer;
                color: ${colors.text};
                font-size: 14px;
                transition: background-color 0.2s;
                border-bottom: 1px solid ${colors.backgroundHover};
            `;
            
            // Highlight matching text only if we have meaningful input
            if (trimmedInput.length > 0) {
                // Escape special regex characters in the input
                const escapedInput = trimmedInput.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const regex = new RegExp(`(${escapedInput})`, 'gi');
                const highlightedText = objective.replace(regex, `<mark style="background: #3b82f6; color: white; padding: 0 2px; border-radius: 2px;">$1</mark>`);
                option.innerHTML = highlightedText;
            } else {
                option.textContent = objective;
            }
            
            // Add hover effect
            option.addEventListener('mouseenter', () => {
                option.style.backgroundColor = colors.backgroundHover;
            });
            
            option.addEventListener('mouseleave', () => {
                option.style.backgroundColor = 'transparent';
            });
            
            // Add click handler
            option.addEventListener('click', () => {
                const nameInput = dropdown.parentElement.querySelector('.objective-block__modal-name-input');
                if (nameInput) {
                    nameInput.value = objective;
                    // Trigger input event to update tempData
                    const event = new Event('input', { bubbles: true });
                    nameInput.dispatchEvent(event);
                }
                dropdown.style.display = 'none';
            });
            
            dropdown.appendChild(option);
        });
        
        dropdown.style.display = filteredObjectives.length > 0 ? 'block' : 'none';
    }

    createTempPreviewContent(tempData) {
        const operatorClass = `objective-block__display--${tempData.operator}`;
        const operatorIcon = this.getOperatorIcon(tempData.operator);
        const operatorText = this.t[`operator${tempData.operator.charAt(0).toUpperCase() + tempData.operator.slice(1)}`];
        const displayName = tempData.name || (this.t.previewSample || 'Sample Objective');
        
        return `
            <div class="objective-block__display ${operatorClass}">
                <div class="objective-block__display-header">
                    <div class="objective-block__display-icon">
                        ${operatorIcon}
                    </div>
                    <div class="objective-block__display-meta">
                        <div class="objective-block__display-label">${this.t.objectiveLabel || 'Objective'}</div>
                        <div class="objective-block__display-operator-text">${operatorText}</div>
                    </div>
                    <div class="objective-block__display-percentage">
                        <span class="objective-block__display-percentage-value">${tempData.percentage || 0}</span>
                        <span class="objective-block__display-percentage-symbol">%</span>
                    </div>
                </div>
                <div class="objective-block__display-content">
                    <h3 class="objective-block__display-name">${displayName}</h3>
                    <div class="objective-block__display-progress">
                        <div class="objective-block__display-progress-bar">
                            <div class="objective-block__display-progress-fill" style="width: ${Math.min(tempData.percentage || 0, 100)}%"></div>
                        </div>
                        <div class="objective-block__display-progress-text">
                            <span>${this.t.target || 'Target'}: ${tempData.percentage || 0}${(tempData.unit || 'percentage') === 'percentage' ? '%' : ''}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    validateField(field, value) {
        if (!field) return true;
        
        // Find error element by walking up the DOM tree more carefully
        let errorElement = null;
        let currentElement = field.parentElement;
        let maxLevels = 5; // Prevent infinite loops
        
        // Try to find error element in parent containers
        while (currentElement && !errorElement && maxLevels > 0) {
            errorElement = currentElement.querySelector('.objective-block__modal-field-error');
            if (!errorElement) {
                currentElement = currentElement.parentElement;
                maxLevels--;
            }
        }
        
        // If no error element found, try the next sibling approach
        if (!errorElement) {
            const nextElement = field.nextElementSibling;
            if (nextElement && nextElement.classList.contains('objective-block__modal-field-error')) {
                errorElement = nextElement;
            }
        }
        
        if (field.classList.contains('objective-block__modal-name-input')) {
            if (!value || value.trim() === '') {
                this.showFieldError(field, errorElement, this.t.emptyObjective || 'Please enter an objective name');
                return false;
            }
        } else if (field.classList.contains('objective-block__modal-percentage-input')) {
            const numValue = parseFloat(value);
            if (isNaN(numValue) || numValue < 0) {
                this.showFieldError(field, errorElement, this.t.invalidValue || 'Please enter a valid positive number');
                return false;
            }
        }
        
        this.clearFieldError(field, errorElement);
        return true;
    }

    validateForm(data) {
        if (!this.modal) return false;
        
        const modal = this.modal.querySelector('div'); // Get the modal div directly
        if (!modal) return false;
        
        const nameInput = modal.querySelector('.objective-block__modal-name-input');
        const percentageInput = modal.querySelector('.objective-block__modal-percentage-input');
        
        let isValid = true;
        
        if (nameInput && !this.validateField(nameInput, data.name)) {
            isValid = false;
        }
        
        if (percentageInput && !this.validateField(percentageInput, data.percentage)) {
            isValid = false;
        }
        
        return isValid;
    }

    showFieldError(field, errorElement, message) {
        field.style.borderColor = '#ef4444';
        field.style.boxShadow = '0 0 0 2px rgba(239, 68, 68, 0.2)';
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    clearFieldError(field, errorElement) {
        field.style.borderColor = '#d1d5db';
        field.style.boxShadow = 'none';
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }

    closeModal() {
        if (this.modal) {
            this.modal.classList.remove('active');
            setTimeout(() => {
                if (this.modal && this.modal.parentNode) {
                    document.body.removeChild(this.modal);
                }
                this.modal = null;
                this.isEditing = false;
            }, 300);
        }
    }

    updateBlockDisplay() {
        if (this.wrapper) {
            // Clear existing content
            this.wrapper.innerHTML = '';
            
            // Add new content
            if (this.isEmpty()) {
                const placeholder = this.createPlaceholder();
                this.wrapper.appendChild(placeholder);
            } else {
                const display = this.createObjectiveDisplay();
                this.wrapper.appendChild(display);
            }
        }
    }

    validateAndNotify() {
        // Only notify of changes if data is valid
        if (this.validate(this.data)) {
            // Notify EditorJS of data changes
            if (this.blockAPI && this.blockAPI.dispatchChange) {
                this.blockAPI.dispatchChange();
            }
        }
    }

    save() {
        return {
            name: this.data.name || '',
            operator: this.data.operator || 'increase',
            percentage: parseFloat(this.data.percentage) || 0,
            unit: this.data.unit || 'percentage'
        };
    }

    validate(savedData) {
        // Allow empty data for new blocks
        if (!savedData || Object.keys(savedData).length === 0) {
            return true;
        }

        // Check if we have a name
        if (!savedData.name || savedData.name.trim() === '') {
            return false;
        }

        // Check if operator is valid
        const validOperators = ['increase', 'decrease', 'equal'];
        if (!validOperators.includes(savedData.operator)) {
            return false;
        }

        // Check if percentage/number value is valid
        const value = parseFloat(savedData.percentage);
        if (isNaN(value) || value < 0) {
            return false;
        }

        return true;
    }

    static get sanitize() {
        return {
            name: {
                br: false,
            },
            operator: {},
            percentage: {},
            unit: {}
        };
    }

    /**
     * Check if the block is empty (no meaningful content)
     */
    isEmpty() {
        return !this.data.name || this.data.name.trim() === '';
    }

    /**
     * Get the objective display text for rendering
     */
    getDisplayText() {
        if (this.isEmpty()) {
            return '';
        }

        const operatorText = this.t[`operator${this.data.operator.charAt(0).toUpperCase() + this.data.operator.slice(1)}`];
        return `${this.data.name}: ${operatorText} ${this.data.percentage}%`;
    }

    /**
     * Render the objective block to HTML (for read-only display)
     */
    renderHTML() {
        if (this.isEmpty()) {
            return '';
        }

        const operatorClass = `objective-block__display--${this.data.operator}`;
        const displayText = this.getDisplayText();

        return `
            <div class="objective-block__display ${operatorClass}">
                <div class="objective-block__display-content">
                    <span class="objective-block__display-name">${this.data.name}</span>
                    <span class="objective-block__display-operator">${this.t[`operator${this.data.operator.charAt(0).toUpperCase() + this.data.operator.slice(1)}`]}</span>
                    <span class="objective-block__display-percentage">${this.data.percentage}%</span>
                </div>
            </div>
        `;
    }

    /**
     * Clean up resources when block is removed
     */
    destroy() {
        // Close modal if open
        this.closeModal();
        
        // Clear references
        this.wrapper = null;
        this.modal = null;
        this.isEditing = false;
    }
}

export default ObjectiveBlock;

