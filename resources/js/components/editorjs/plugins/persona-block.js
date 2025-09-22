/**
 * PersonaBlock Plugin for EditorJS
 * Allows creation of user personas with demographics, interests, and preferences
 */

class PersonaBlock {
    static get toolbox() {
        return {
            title: 'Persona',
            icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
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
            age_range: this.data.age_range || this.config.defaultAgeRange || '25-34',
            gender: this.data.gender || this.config.defaultGender || 'both',
            location: this.data.location || '',
            interests: this.data.interests || '',
            preferred_channel: this.data.preferred_channel || this.config.defaultChannel || 'email',
            color: this.data.color || this.config.defaultColor || 'blue',
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
        
        // Define translations for PersonaBlock plugin
        const translations = {
            'en': {
                namePlaceholder: 'Enter persona name...',
                locationPlaceholder: 'Enter location...',
                interestsPlaceholder: 'Enter interests (separated by commas)...',
                placeholderTitle: 'Create User Persona',
                placeholderSubtitle: 'Click to define target audience profiles',
                addPersona: 'Add Persona',
                editPersona: 'Edit Persona',
                personaLabel: 'Persona',
                edit: 'Edit',
                close: 'Close',
                personaName: 'Persona Name',
                ageRange: 'Age Range',
                gender: 'Gender',
                location: 'Location',
                interests: 'Interests',
                preferredChannel: 'Preferred Channel',
                color: 'Color',
                preview: 'Preview',
                cancel: 'Cancel',
                save: 'Save Persona'
            },
            'ar': {
                namePlaceholder: 'أدخل اسم الشخصية...',
                locationPlaceholder: 'أدخل الموقع...',
                interestsPlaceholder: 'أدخل الاهتمامات (مفصولة بفواصل)...',
                placeholderTitle: 'إنشاء شخصية المستخدم',
                placeholderSubtitle: 'انقر لتحديد ملفات الجمهور المستهدف',
                addPersona: 'إضافة شخصية',
                editPersona: 'تعديل الشخصية',
                personaLabel: 'الشخصية',
                edit: 'تعديل',
                close: 'إغلاق',
                personaName: 'اسم الشخصية',
                ageRange: 'الفئة العمرية',
                gender: 'الجنس',
                location: 'الموقع',
                interests: 'الاهتمامات',
                preferredChannel: 'القناة المفضلة',
                color: 'اللون',
                preview: 'معاينة',
                cancel: 'إلغاء',
                save: 'حفظ الشخصية'
            }
        };
        
        return translations[locale] || translations['en'];
    }

    render() {
        const wrapper = document.createElement('div');
        wrapper.classList.add('persona-block');

        // Store wrapper reference for later use
        this.wrapper = wrapper;

        // Check if we have existing data to display
        if (this.isEmpty() && !this.readOnly) {
            // Show placeholder for new blocks
            const placeholder = this.createPlaceholder();
            wrapper.appendChild(placeholder);
        } else {
            // Show the persona display
            const display = this.createPersonaDisplay();
            wrapper.appendChild(display);
        }

        return wrapper;
    }

    isDarkMode() {
        return document.documentElement.classList.contains('dark');
    }

    createPlaceholder() {
        const placeholder = document.createElement('div');
        placeholder.classList.add('persona-block__placeholder');
        
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
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
                    </svg>
                </div>
                <div style="flex: 1; font-size: 14px; font-weight: 500;">
                    ${this.t.placeholderTitle || 'Create User Persona'} 
                    <span style="font-weight: 400; opacity: 0.8;">• ${this.t.placeholderSubtitle || 'Click to define target audience profiles'}</span>
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
                    ${this.t.addPersona || 'Add Persona'}
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

    createPersonaDisplay() {
        const display = document.createElement('div');
        display.classList.add('persona-block__display-container');
        
        // Detect dark mode
        const isDarkMode = this.isDarkMode();
        
        // Get persona color
        const colorHex = this.getColorHex(this.data.color);
        
        display.innerHTML = `
            <div class="persona-block__display" style="
                display: flex;
                flex-direction: column;
                gap: 12px;
                padding: 16px;
                margin-bottom: 16px;
                background: ${isDarkMode ? '#27272a' : '#f4f4f5'};
                border: 1px solid ${isDarkMode ? '#3f3f46' : '#e4e4e7'};
                border-left: 4px solid ${colorHex};
                border-radius: 6px;
                color: ${isDarkMode ? '#fafafa' : '#18181b'};
                font-weight: 500;
                font-size: 14px;
                line-height: 1.3;
            ">
                <div style="
                    display: flex;
                    align-items: center;
                    gap: 12px;
                ">
                    <div style="
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 32px;
                        height: 32px;
                        background: ${colorHex}20;
                        border-radius: 6px;
                        color: ${colorHex};
                        flex-shrink: 0;
                    ">
                        ${this.getPersonaIcon()}
                    </div>
                    <div style="flex: 1;">
                        <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: ${isDarkMode ? '#fafafa' : '#18181b'};">
                            ${this.data.name || 'Unnamed Persona'}
                        </h3>
                        <div style="
                            margin-top: 4px;
                            font-size: 12px;
                            color: ${isDarkMode ? '#a1a1aa' : '#71717a'};
                            display: flex;
                            align-items: center;
                            gap: 8px;
                            flex-wrap: wrap;
                        ">
                            <span>${this.getGenderDisplayName()}</span>
                            <span>•</span>
                            <span>${this.data.age_range}</span>
                            <span>•</span>
                            <span>${this.data.location}</span>
                        </div>
                    </div>
                </div>
                ${this.data.interests ? `
                    <div style="
                        display: flex;
                        flex-wrap: wrap;
                        gap: 6px;
                        margin-top: 8px;
                    ">
                        ${this.getInterestsArray().map(interest => `
                            <span style="
                                display: inline-block;
                                padding: 2px 8px;
                                background: ${isDarkMode ? '#3f3f46' : '#e4e4e7'};
                                color: ${isDarkMode ? '#d4d4d8' : '#3f3f46'};
                                border-radius: 12px;
                                font-size: 11px;
                                font-weight: 500;
                            ">${interest.trim()}</span>
                        `).join('')}
                    </div>
                ` : ''}
                <div style="
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin-top: 4px;
                    font-size: 12px;
                    color: ${isDarkMode ? '#a1a1aa' : '#71717a'};
                ">
                    <span><strong>Preferred Channel:</strong> ${this.getChannelDisplayName()}</span>
                </div>
            </div>
        `;
        
        return display;
    }

    getPersonaIcon() {
        return `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
        </svg>`;
    }

    getColorHex(colorName) {
        const colors = this.config.predefinedColors || {};
        
        return colors[colorName] || colors['blue'] || '#3b82f6';
    }

    getGenderDisplayName() {
        const genders = this.config.predefinedGenders || {};
        
        return genders[this.data.gender] || this.data.gender;
    }

    getChannelDisplayName() {
        const channels = this.config.predefinedChannels || {};
        
        return channels[this.data.preferred_channel] || this.data.preferred_channel;
    }

    getInterestsArray() {
        if (!this.data.interests) return [];
        return this.data.interests.split(',').map(interest => interest.trim()).filter(interest => interest.length > 0);
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
        backdrop.setAttribute('data-modal', 'persona-block');
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
        modal.setAttribute('aria-labelledby', 'persona-modal-title');
        modal.setAttribute('aria-modal', 'true');
        modal.style.cssText = `
            background: ${isDarkMode ? '#18181b' : '#ffffff'};
            color: ${isDarkMode ? '#fafafa' : '#18181b'};
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, ${isDarkMode ? '0.3' : '0.1'}), 0 10px 10px -5px rgba(0, 0, 0, ${isDarkMode ? '0.2' : '0.04'});
            max-width: 600px;
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
            const nameInput = modal.querySelector('.persona-block__modal-name-input');
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
        
        // Create options for dropdowns
        const genderOptions = Object.entries(this.config.predefinedGenders || {}).map(([key, value]) => 
            `<option value="${key}" ${this.data.gender === key ? 'selected' : ''}>${value}</option>`
        ).join('');
        
        const ageRangeOptions = Object.entries(this.config.predefinedAgeRanges || {}).map(([key, value]) => 
            `<option value="${key}" ${this.data.age_range === key ? 'selected' : ''}>${value}</option>`
        ).join('');
        
        const channelOptions = Object.entries(this.config.predefinedChannels || {}).map(([key, value]) => 
            `<option value="${key}" ${this.data.preferred_channel === key ? 'selected' : ''}>${value}</option>`
        ).join('');
        
        const colorOptions = Object.entries(this.config.predefinedColors || {}).map(([colorKey, colorHex]) => {
            const isSelected = this.data.color === colorKey;
            return `
                <div class="persona-block__modal-color-option" data-color="${colorKey}" style="
                    width: 32px;
                    height: 32px;
                    border-radius: 6px;
                    background: ${colorHex};
                    cursor: pointer;
                    border: 2px solid ${isSelected ? colors.inputFocus : 'transparent'};
                    transition: all 0.2s;
                    position: relative;
                " title="${colorKey}">
                    ${isSelected ? `<div style="
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        color: white;
                        font-weight: bold;
                    ">✓</div>` : ''}
                </div>
            `;
        }).join('');
        
        return `
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 24px 24px 0 24px; border-bottom: 1px solid ${colors.border};">
                <h2 id="persona-modal-title" style="margin: 0; font-size: 18px; font-weight: 600; color: ${colors.text};">
                    ${this.isEmpty() ? (this.t.addPersona || 'Add Persona') : (this.t.editPersona || 'Edit Persona')}
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
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <!-- Left Column -->
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <!-- Persona Name -->
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label for="persona-name" style="font-size: 14px; font-weight: 500; color: ${colors.textSecondary};">
                                ${this.t.personaName || 'Persona Name'}
                            </label>
                            <input 
                                type="text" 
                                id="persona-name"
                                class="persona-block__modal-name-input"
                                placeholder="${this.t.namePlaceholder || 'Enter persona name...'}"
                                value="${this.data.name || ''}"
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
                            <div class="persona-block__modal-field-error" style="display: none; font-size: 12px; color: #ef4444; margin-top: 4px;"></div>
                        </div>
                        
                        <!-- Age Range -->
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label for="persona-age-range" style="font-size: 14px; font-weight: 500; color: ${colors.textSecondary};">
                                ${this.t.ageRange || 'Age Range'}
                            </label>
                            <select 
                                id="persona-age-range"
                                class="persona-block__modal-age-range-select"
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
                            >
                                ${ageRangeOptions}
                            </select>
                        </div>
                        
                        <!-- Gender -->
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label for="persona-gender" style="font-size: 14px; font-weight: 500; color: ${colors.textSecondary};">
                                ${this.t.gender || 'Gender'}
                            </label>
                            <select 
                                id="persona-gender"
                                class="persona-block__modal-gender-select"
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
                            >
                                ${genderOptions}
                            </select>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <!-- Location -->
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label for="persona-location" style="font-size: 14px; font-weight: 500; color: ${colors.textSecondary};">
                                ${this.t.location || 'Location'}
                            </label>
                            <input 
                                type="text" 
                                id="persona-location"
                                class="persona-block__modal-location-input"
                                placeholder="${this.t.locationPlaceholder || 'Enter location...'}"
                                value="${this.data.location || ''}"
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
                            <div class="persona-block__modal-field-error" style="display: none; font-size: 12px; color: #ef4444; margin-top: 4px;"></div>
                        </div>
                        
                        <!-- Preferred Channel -->
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label for="persona-channel" style="font-size: 14px; font-weight: 500; color: ${colors.textSecondary};">
                                ${this.t.preferredChannel || 'Preferred Channel'}
                            </label>
                            <select 
                                id="persona-channel"
                                class="persona-block__modal-channel-select"
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
                            >
                                ${channelOptions}
                            </select>
                        </div>
                        
                        <!-- Color Selector -->
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-size: 14px; font-weight: 500; color: ${colors.textSecondary};">
                                ${this.t.color || 'Color'}
                            </label>
                            <div class="persona-block__modal-color-grid" style="
                                display: grid;
                                grid-template-columns: repeat(6, 1fr);
                                gap: 8px;
                                padding: 8px;
                                border: 1px solid ${colors.inputBorder};
                                border-radius: 8px;
                                background: ${colors.input};
                            ">
                                ${colorOptions}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Full Width Interests -->
                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 20px;">
                    <label for="persona-interests" style="font-size: 14px; font-weight: 500; color: ${colors.textSecondary};">
                        ${this.t.interests || 'Interests'}
                    </label>
                    <textarea 
                        id="persona-interests"
                        class="persona-block__modal-interests-input"
                        placeholder="${this.t.interestsPlaceholder || 'Enter interests (separated by commas)...'}"
                        rows="3"
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
                            resize: vertical;
                            font-family: inherit;
                        "
                        onfocus="this.style.borderColor='${colors.inputFocus}'; this.style.outline='none'"
                        onblur="this.style.borderColor='${colors.inputBorder}'"
                    >${this.data.interests || ''}</textarea>
                    <div class="persona-block__modal-field-error" style="display: none; font-size: 12px; color: #ef4444; margin-top: 4px;"></div>
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
                    ${this.t.save || 'Save Persona'}
                </button>
            </div>
        `;
    }

    initializeModal(backdrop, modal) {
        const nameInput = modal.querySelector('.persona-block__modal-name-input');
        const locationInput = modal.querySelector('.persona-block__modal-location-input');
        const interestsInput = modal.querySelector('.persona-block__modal-interests-input');
        const ageRangeSelect = modal.querySelector('.persona-block__modal-age-range-select');
        const genderSelect = modal.querySelector('.persona-block__modal-gender-select');
        const channelSelect = modal.querySelector('.persona-block__modal-channel-select');
        const colorOptions = modal.querySelectorAll('.persona-block__modal-color-option');
        const saveButton = modal.querySelector('[data-action="save"]');
        const cancelButton = modal.querySelector('[data-action="cancel"]');
        const closeButton = modal.querySelector('[aria-label*="Close"]');
        
        // Temporary data for form
        let tempData = { ...this.data };
        
        // Input handlers
        if (nameInput) {
            nameInput.addEventListener('input', (e) => {
                tempData.name = e.target.value;
                this.validateField(nameInput, tempData.name);
            });
        }
        
        if (locationInput) {
            locationInput.addEventListener('input', (e) => {
                tempData.location = e.target.value;
                this.validateField(locationInput, tempData.location);
            });
        }
        
        if (interestsInput) {
            interestsInput.addEventListener('input', (e) => {
                tempData.interests = e.target.value;
                this.validateField(interestsInput, tempData.interests);
            });
        }
        
        if (ageRangeSelect) {
            ageRangeSelect.addEventListener('change', (e) => {
                tempData.age_range = e.target.value;
            });
        }
        
        if (genderSelect) {
            genderSelect.addEventListener('change', (e) => {
                tempData.gender = e.target.value;
            });
        }
        
        if (channelSelect) {
            channelSelect.addEventListener('change', (e) => {
                tempData.preferred_channel = e.target.value;
            });
        }
        
        // Color selection
        colorOptions.forEach(option => {
            option.addEventListener('click', () => {
                const selectedColor = option.dataset.color;
                tempData.color = selectedColor;
                
                // Update visual selection
                colorOptions.forEach(opt => {
                    opt.style.border = '2px solid transparent';
                    opt.innerHTML = '';
                });
                
                option.style.border = '2px solid #3b82f6';
                option.innerHTML = `<div style="
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    color: white;
                    font-weight: bold;
                ">✓</div>`;
            });
        });
        
        // Save button
        saveButton.addEventListener('click', () => {
            console.log('Saving persona data:', tempData);
            
            if (this.validateForm(tempData)) {
                this.data = { ...tempData };
                console.log('Persona saved successfully:', this.data);
                this.closeModal();
                this.updateBlockDisplay();
                this.validateAndNotify();
            } else {
                console.log('Validation failed for persona data:', tempData);
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

    validateField(field, value) {
        if (!field) return true;
        
        // Find error element
        let errorElement = null;
        let currentElement = field.parentElement;
        let maxLevels = 5;
        
        while (currentElement && !errorElement && maxLevels > 0) {
            errorElement = currentElement.querySelector('.persona-block__modal-field-error');
            if (!errorElement) {
                currentElement = currentElement.parentElement;
                maxLevels--;
            }
        }
        
        if (field.classList.contains('persona-block__modal-name-input')) {
            if (!value || value.trim() === '') {
                this.showFieldError(field, errorElement, 'Please enter a persona name');
                return false;
            }
        } else if (field.classList.contains('persona-block__modal-location-input')) {
            if (!value || value.trim() === '') {
                this.showFieldError(field, errorElement, 'Please enter a location');
                return false;
            }
        } else if (field.classList.contains('persona-block__modal-interests-input')) {
            if (!value || value.trim() === '') {
                this.showFieldError(field, errorElement, 'Please enter interests');
                return false;
            }
        }
        
        this.clearFieldError(field, errorElement);
        return true;
    }

    validateForm(data) {
        if (!this.modal) return false;
        
        const modal = this.modal.querySelector('div');
        if (!modal) return false;
        
        const nameInput = modal.querySelector('.persona-block__modal-name-input');
        const locationInput = modal.querySelector('.persona-block__modal-location-input');
        const interestsInput = modal.querySelector('.persona-block__modal-interests-input');
        
        let isValid = true;
        
        if (nameInput && !this.validateField(nameInput, data.name)) {
            isValid = false;
        }
        
        if (locationInput && !this.validateField(locationInput, data.location)) {
            isValid = false;
        }
        
        if (interestsInput && !this.validateField(interestsInput, data.interests)) {
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
                const display = this.createPersonaDisplay();
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
            age_range: this.data.age_range || '25-34',
            gender: this.data.gender || 'both',
            location: this.data.location || '',
            interests: this.data.interests || '',
            preferred_channel: this.data.preferred_channel || 'email',
            color: this.data.color || 'blue'
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

        return true;
    }

    static get sanitize() {
        return {
            name: {
                br: false,
            },
            age_range: {},
            gender: {},
            location: {
                br: false,
            },
            interests: {
                br: false,
            },
            preferred_channel: {},
            color: {}
        };
    }

    /**
     * Check if the block is empty (no meaningful content)
     */
    isEmpty() {
        return !this.data.name || this.data.name.trim() === '';
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

export default PersonaBlock;