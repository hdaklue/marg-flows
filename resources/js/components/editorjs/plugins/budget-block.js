/**
 * BudgetBlock Plugin for EditorJS
 * Allows creation of budget entries with name, amount, and currency
 */

class BudgetBlock {
    static get toolbox() {
        return {
            title: 'Budget',
            icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
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
            amount: this.data.amount || 0,
            currency: this.data.currency || 'USD',
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
        
        // Define translations for BudgetBlock plugin
        const translations = {
            'en': {
                namePlaceholder: 'Enter budget name...',
                amountPlaceholder: '0.00',
                currencyPlaceholder: 'USD',
                invalidAmount: 'Please enter a valid amount',
                emptyBudget: 'Please enter a budget name',
                placeholderTitle: 'Set Your Budget',
                placeholderSubtitle: 'Click to define budget allocation',
                addBudget: 'Add Budget',
                editBudget: 'Edit Budget',
                budgetLabel: 'Budget',
                amount: 'Amount',
                currency: 'Currency',
                edit: 'Edit',
                close: 'Close',
                budgetName: 'Budget Name',
                budgetAmount: 'Budget Amount',
                budgetCurrency: 'Currency',
                cancel: 'Cancel',
                save: 'Save Budget',
                customCurrency: 'Custom Currency'
            },
            'ar': {
                namePlaceholder: 'أدخل اسم الميزانية...',
                amountPlaceholder: '0.00',
                currencyPlaceholder: 'USD',
                invalidAmount: 'يرجى إدخال مبلغ صحيح',
                emptyBudget: 'يرجى إدخال اسم الميزانية',
                placeholderTitle: 'حدد ميزانيتك',
                placeholderSubtitle: 'انقر لتحديد تخصيص الميزانية',
                addBudget: 'إضافة ميزانية',
                editBudget: 'تعديل الميزانية',
                budgetLabel: 'الميزانية',
                amount: 'المبلغ',
                currency: 'العملة',
                edit: 'تعديل',
                close: 'إغلاق',
                budgetName: 'اسم الميزانية',
                budgetAmount: 'مبلغ الميزانية',
                budgetCurrency: 'العملة',
                cancel: 'إلغاء',
                save: 'حفظ الميزانية',
                customCurrency: 'عملة مخصصة'
            }
        };
        
        return translations[locale] || translations['en'];
    }

    render() {
        const wrapper = document.createElement('div');
        wrapper.classList.add('budget-block');

        // Store wrapper reference for later use
        this.wrapper = wrapper;

        // Check if we have existing data to display
        if (this.isEmpty() && !this.readOnly) {
            // Show placeholder for new blocks
            const placeholder = this.createPlaceholder();
            wrapper.appendChild(placeholder);
        } else {
            // Show the budget display
            const display = this.createBudgetDisplay();
            wrapper.appendChild(display);
        }

        return wrapper;
    }

    isDarkMode() {
        return document.documentElement.classList.contains('dark');
    }

    createPlaceholder() {
        const placeholder = document.createElement('div');
        placeholder.classList.add('budget-block__placeholder');
        
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
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div style="flex: 1; font-size: 14px; font-weight: 500;">
                    ${this.t.placeholderTitle || 'Set Your Budget'} 
                    <span style="font-weight: 400; opacity: 0.8;">• ${this.t.placeholderSubtitle || 'Click to define budget allocation'}</span>
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
                    ${this.t.addBudget || 'Add Budget'}
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

    createBudgetDisplay() {
        const display = document.createElement('div');
        display.classList.add('budget-block__display-container');
        
        // Detect dark mode
        const isDarkMode = this.isDarkMode();
        
        // Create the translated budget text
        const translatedText = this.getTranslatedBudgetText();
        
        display.innerHTML = `
            <div class="budget-block__display" style="
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
                    background: ${isDarkMode ? '#0369a1' : '#e0f2fe'};
                    border-radius: 4px;
                    color: ${isDarkMode ? '#38bdf8' : '#0369a1'};
                    flex-shrink: 0;
                ">
                    ${this.getBudgetIcon()}
                </div>
                <div style="flex: 1; overflow: hidden;">
                    ${translatedText}
                </div>
            </div>
        `;
        
        return display;
    }

    getTranslatedBudgetText() {
        // Get currency symbol
        const currencySymbol = this.getCurrencySymbol(this.data.currency);
        
        // Format the amount with commas
        const formattedAmount = this.formatNumber(this.data.amount);
        
        // Detect current locale
        const htmlElement = document.documentElement;
        const currentLocale = htmlElement.lang || 'en';
        const locale = currentLocale.split('-')[0]; // Get base locale (e.g., 'en' from 'en-US')
        
        // Create translated text based on language
        if (locale === 'ar') {
            return `${this.data.name}: ${currencySymbol}${formattedAmount}`;
        } else {
            return `${this.data.name}: ${currencySymbol}${formattedAmount}`;
        }
    }

    getCurrencySymbol(currency) {
        const currencies = this.config.predefinedCurrencies || {
            'USD': '$',
            'EUR': '€',
            'GBP': '£',
            'JPY': '¥',
            'SAR': 'ر.س',
            'AED': 'د.إ',
            'KWD': 'د.ك',
            'QAR': 'ر.ق',
            'BHD': 'د.ب',
            'OMR': 'ر.ع',
        };
        
        return currencies[currency] || currency;
    }

    formatNumber(num) {
        // Convert to number if it's a string
        const number = parseFloat(num) || 0;
        
        // Use toLocaleString for proper number formatting with commas
        return number.toLocaleString('en-US', {
            maximumFractionDigits: 2,
            minimumFractionDigits: 2
        });
    }

    formatNumberInput(value) {
        console.log('formatNumberInput input:', value);
        
        // Handle empty or invalid input
        if (!value || value === '') {
            return '';
        }
        
        // Remove any non-digit characters except decimal point
        const cleanValue = value.replace(/[^\d.]/g, '');
        console.log('cleanValue:', cleanValue);
        
        // Handle multiple decimal points - keep only the first one
        const parts = cleanValue.split('.');
        let integerPart = parts[0] || '0'; // Default to '0' if empty
        let decimalPart = parts[1];
        
        console.log('integerPart:', integerPart, 'decimalPart:', decimalPart);
        
        // Handle case where integer part is empty or just zeros
        if (integerPart === '' || integerPart === '0') {
            integerPart = '0';
        } else {
            // Add commas to integer part (only if it's more than 3 digits)
            integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        
        // Reconstruct the number
        let formattedValue = integerPart;
        if (decimalPart !== undefined) {
            // Limit decimal places to 2
            decimalPart = decimalPart.substring(0, 2);
            formattedValue += '.' + decimalPart;
        }
        
        console.log('formatNumberInput output:', formattedValue);
        return formattedValue;
    }


    getBudgetIcon() {
        return `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
        </svg>`;
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
        backdrop.setAttribute('data-modal', 'budget-block');
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
        modal.setAttribute('aria-labelledby', 'budget-modal-title');
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
            const nameInput = modal.querySelector('.budget-block__modal-name-input');
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
                <h2 id="budget-modal-title" style="margin: 0; font-size: 18px; font-weight: 600; color: ${colors.text};">
                    ${this.isEmpty() ? (this.t.addBudget || 'Add Budget') : (this.t.editBudget || 'Edit Budget')}
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
                    <!-- Budget Name -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="budget-name" style="font-size: 14px; font-weight: 500; color: ${colors.textSecondary};">
                            ${this.t.budgetName || 'Budget Name'}
                        </label>
                        <div style="position: relative;">
                            <input 
                                type="text" 
                                id="budget-name"
                                class="budget-block__modal-name-input"
                                placeholder="${this.t.namePlaceholder || 'Enter budget name...'}"
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
                            <div class="budget-block__modal-dropdown" style="
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
                        <div class="budget-block__modal-field-error" style="display: none; font-size: 12px; color: #ef4444; margin-top: 4px;"></div>
                    </div>
                    
                    <!-- Budget Amount -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="budget-amount" style="font-size: 14px; font-weight: 500; color: ${colors.textSecondary};">
                            ${this.t.budgetAmount || 'Budget Amount'}
                        </label>
                        <input 
                            type="text" 
                            id="budget-amount"
                            class="budget-block__modal-amount-input"
                            value="${this.data.amount ? this.formatNumberInput(this.data.amount.toString()) : ''}"
                            placeholder="${this.t.amountPlaceholder || '0.00'}"
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
                        <div class="budget-block__modal-field-error" style="display: none; font-size: 12px; color: #ef4444; margin-top: 4px;"></div>
                    </div>
                    
                    <!-- Currency Selection -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="budget-currency" style="font-size: 14px; font-weight: 500; color: ${colors.textSecondary};">
                            ${this.t.budgetCurrency || 'Currency'}
                        </label>
                        <select 
                            id="budget-currency"
                            class="budget-block__modal-currency-select"
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
                                cursor: pointer;
                            "
                            onfocus="this.style.borderColor='${colors.inputFocus}'; this.style.outline='none'"
                            onblur="this.style.borderColor='${colors.inputBorder}'"
                        >
                            ${this.createCurrencyOptions()}
                        </select>
                        <div class="budget-block__modal-field-error" style="display: none; font-size: 12px; color: #ef4444; margin-top: 4px;"></div>
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
                    ${this.t.save || 'Save Budget'}
                </button>
            </div>
        `;
    }

    createCurrencyOptions() {
        const currencies = this.config.predefinedCurrencies || {
            'USD': '$',
            'EUR': '€',
            'GBP': '£',
            'JPY': '¥',
            'SAR': 'ر.س',
            'AED': 'د.إ',
            'KWD': 'د.ك',
            'QAR': 'ر.ق',
            'BHD': 'د.ب',
            'OMR': 'ر.ع',
        };

        let options = '';
        
        for (const [code, symbol] of Object.entries(currencies)) {
            const selected = this.data.currency === code ? 'selected' : '';
            options += `<option value="${code}" ${selected}>${code} (${symbol})</option>`;
        }
        
        return options;
    }

    initializeModal(backdrop, modal) {
        const nameInput = modal.querySelector('.budget-block__modal-name-input');
        const amountInput = modal.querySelector('.budget-block__modal-amount-input');
        const currencySelect = modal.querySelector('.budget-block__modal-currency-select');
        const saveButton = modal.querySelector('[data-action="save"]');
        const cancelButton = modal.querySelector('[data-action="cancel"]');
        const closeButton = modal.querySelector('[aria-label*="Close"]');
        const dropdown = modal.querySelector('.budget-block__modal-dropdown');
        
        // Temporary data for form
        let tempData = { ...this.data };
        
        // Get predefined budgets from config
        const predefinedBudgets = this.config.predefinedBudgets || [];
        
        // Initialize dropdown functionality
        this.initializeDropdown(nameInput, dropdown, predefinedBudgets, tempData);
        
        // Name input handler (updated to work with dropdown)
        if (nameInput) {
            nameInput.addEventListener('input', (e) => {
                tempData.name = e.target.value;
                this.validateField(nameInput, tempData.name);
                this.filterDropdownOptions(dropdown, e.target.value, predefinedBudgets);
            });
        }
        
        // Amount input handler - simple input tracking
        if (amountInput) {
            amountInput.addEventListener('input', (e) => {
                // Only allow numbers and decimal point
                let inputValue = e.target.value;
                let cleanValue = inputValue.replace(/[^\d.]/g, '');
                
                // Handle multiple decimal points - keep only the first one
                const parts = cleanValue.split('.');
                if (parts.length > 2) {
                    cleanValue = parts[0] + '.' + parts.slice(1).join('');
                }
                
                // Update input if we cleaned it
                if (inputValue !== cleanValue) {
                    e.target.value = cleanValue;
                }
                
                const numericValue = parseFloat(cleanValue) || 0;
                console.log('Input event - inputValue:', inputValue, 'cleanValue:', cleanValue, 'numericValue:', numericValue);
                tempData.amount = numericValue;
                this.validateField(amountInput, numericValue);
            });
            
            // Format on blur (when user focuses out)
            amountInput.addEventListener('blur', (e) => {
                const rawValue = e.target.value.replace(/,/g, '').trim();
                console.log('Blur event - rawValue:', rawValue);
                
                // Handle empty input
                if (rawValue === '') {
                    e.target.value = '';
                    return;
                }
                
                // Validate the input is a valid number
                if (/^\d*\.?\d*$/.test(rawValue) && !isNaN(parseFloat(rawValue))) {
                    const formattedValue = this.formatNumberInput(rawValue);
                    console.log('Formatted value:', formattedValue);
                    e.target.value = formattedValue;
                } else {
                    console.log('Invalid number format');
                }
            });
            
            // Remove formatting on focus (when user focuses in)
            amountInput.addEventListener('focus', (e) => {
                const rawValue = e.target.value.replace(/,/g, '');
                console.log('Focus event - original value:', e.target.value, 'rawValue:', rawValue);
                e.target.value = rawValue;
            });
        }
        
        // Currency select handler
        if (currencySelect) {
            currencySelect.addEventListener('change', (e) => {
                tempData.currency = e.target.value;
            });
        }
        
        // Save button
        saveButton.addEventListener('click', () => {
            // Get the raw amount value from the input (remove commas)
            if (amountInput) {
                const rawAmount = amountInput.value.replace(/,/g, '');
                tempData.amount = parseFloat(rawAmount) || 0;
            }
            
            console.log('Saving budget data:', tempData);
            
            if (this.validateForm(tempData)) {
                this.data = { ...tempData };
                console.log('Budget saved successfully:', this.data);
                this.closeModal();
                this.updateBlockDisplay();
                this.validateAndNotify();
            } else {
                console.log('Validation failed for budget data:', tempData);
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
    
    initializeDropdown(nameInput, dropdown, predefinedBudgets, tempData) {
        if (!nameInput || !dropdown || !predefinedBudgets.length) return;
        
        const isDarkMode = this.isDarkMode();
        const colors = {
            text: isDarkMode ? '#fafafa' : '#18181b',
            textMuted: isDarkMode ? '#a1a1aa' : '#71717a',
            backgroundHover: isDarkMode ? '#27272a' : '#f4f4f5'
        };
        
        // Show dropdown on focus
        nameInput.addEventListener('focus', () => {
            this.showDropdown(dropdown, predefinedBudgets, colors, nameInput, tempData);
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
    
    showDropdown(dropdown, budgets, colors, nameInput, tempData) {
        dropdown.innerHTML = '';
        
        budgets.forEach((budget, index) => {
            const option = document.createElement('div');
            option.style.cssText = `
                padding: 12px;
                cursor: pointer;
                color: ${colors.text};
                font-size: 14px;
                transition: background-color 0.2s;
                border-bottom: 1px solid ${colors.backgroundHover};
            `;
            
            option.textContent = budget;
            
            // Add hover effect
            option.addEventListener('mouseenter', () => {
                option.style.backgroundColor = colors.backgroundHover;
            });
            
            option.addEventListener('mouseleave', () => {
                option.style.backgroundColor = 'transparent';
            });
            
            // Add click handler
            option.addEventListener('click', () => {
                nameInput.value = budget;
                tempData.name = budget;
                dropdown.style.display = 'none';
                this.validateField(nameInput, tempData.name);
            });
            
            dropdown.appendChild(option);
        });
        
        dropdown.style.display = 'block';
    }
    
    filterDropdownOptions(dropdown, inputValue, predefinedBudgets) {
        if (!dropdown || !predefinedBudgets.length) return;
        
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
            this.showDropdown(dropdown, predefinedBudgets, colors, null, null);
            return;
        }
        
        // Filter budgets based on input
        const filteredBudgets = predefinedBudgets.filter(budget => 
            budget.toLowerCase().includes(trimmedInput.toLowerCase())
        );
        
        // Clear dropdown
        dropdown.innerHTML = '';
        
        if (filteredBudgets.length === 0) {
            dropdown.style.display = 'none';
            return;
        }
        
        // Add filtered options
        filteredBudgets.forEach((budget) => {
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
                const highlightedText = budget.replace(regex, `<mark style="background: #3b82f6; color: white; padding: 0 2px; border-radius: 2px;">$1</mark>`);
                option.innerHTML = highlightedText;
            } else {
                option.textContent = budget;
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
                const nameInput = dropdown.parentElement.querySelector('.budget-block__modal-name-input');
                if (nameInput) {
                    nameInput.value = budget;
                    // Trigger input event to update tempData
                    const event = new Event('input', { bubbles: true });
                    nameInput.dispatchEvent(event);
                }
                dropdown.style.display = 'none';
            });
            
            dropdown.appendChild(option);
        });
        
        dropdown.style.display = filteredBudgets.length > 0 ? 'block' : 'none';
    }

    validateField(field, value) {
        if (!field) return true;
        
        // Find error element by walking up the DOM tree more carefully
        let errorElement = null;
        let currentElement = field.parentElement;
        let maxLevels = 5; // Prevent infinite loops
        
        // Try to find error element in parent containers
        while (currentElement && !errorElement && maxLevels > 0) {
            errorElement = currentElement.querySelector('.budget-block__modal-field-error');
            if (!errorElement) {
                currentElement = currentElement.parentElement;
                maxLevels--;
            }
        }
        
        // If no error element found, try the next sibling approach
        if (!errorElement) {
            const nextElement = field.nextElementSibling;
            if (nextElement && nextElement.classList.contains('budget-block__modal-field-error')) {
                errorElement = nextElement;
            }
        }
        
        if (field.classList.contains('budget-block__modal-name-input')) {
            if (!value || value.trim() === '') {
                this.showFieldError(field, errorElement, this.t.emptyBudget || 'Please enter a budget name');
                return false;
            }
        } else if (field.classList.contains('budget-block__modal-amount-input')) {
            const numValue = parseFloat(value);
            console.log('validateField - amount value:', value, 'parsed:', numValue);
            if (isNaN(numValue) || numValue < 0) {
                this.showFieldError(field, errorElement, this.t.invalidAmount || 'Please enter a valid positive amount');
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
        
        const nameInput = modal.querySelector('.budget-block__modal-name-input');
        const amountInput = modal.querySelector('.budget-block__modal-amount-input');
        
        let isValid = true;
        
        if (nameInput && !this.validateField(nameInput, data.name)) {
            isValid = false;
        }
        
        if (amountInput && !this.validateField(amountInput, data.amount)) {
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
                const display = this.createBudgetDisplay();
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
            amount: parseFloat(this.data.amount) || 0,
            currency: this.data.currency || 'USD'
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

        // Check if amount is valid
        const amount = parseFloat(savedData.amount);
        if (isNaN(amount) || amount < 0) {
            return false;
        }

        // Check if currency is valid
        if (!savedData.currency || savedData.currency.trim() === '') {
            return false;
        }

        return true;
    }

    static get sanitize() {
        return {
            name: {
                br: false,
            },
            amount: {},
            currency: {}
        };
    }

    /**
     * Check if the block is empty (no meaningful content)
     */
    isEmpty() {
        return !this.data.name || this.data.name.trim() === '';
    }

    /**
     * Get the budget display text for rendering
     */
    getDisplayText() {
        if (this.isEmpty()) {
            return '';
        }

        const symbol = this.getCurrencySymbol(this.data.currency);
        const formattedAmount = this.formatNumber(this.data.amount);
        return `${this.data.name}: ${symbol}${formattedAmount}`;
    }

    /**
     * Render the budget block to HTML (for read-only display)
     */
    renderHTML() {
        if (this.isEmpty()) {
            return '';
        }

        const displayText = this.getDisplayText();

        return `
            <div class="budget-block__display budget-block--budget">
                <div class="budget-block__display-content">
                    <span class="budget-block__display-name">${this.data.name}</span>
                    <span class="budget-block__display-amount">${this.getCurrencySymbol(this.data.currency)}${this.formatNumber(this.data.amount)}</span>
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

export default BudgetBlock;