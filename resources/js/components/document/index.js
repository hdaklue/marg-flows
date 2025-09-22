import EditorJS from "@editorjs/editorjs";
import Header from "@editorjs/header";
import EditorJsList from "@editorjs/list";
import Paragraph from "@editorjs/paragraph";
import Table from "@editorjs/table";
import dayjs from 'dayjs';
import 'dayjs/locale/ar';
import 'dayjs/locale/en';
import relativeTime from 'dayjs/plugin/relativeTime';
import Alert from 'editorjs-alert';
import DragDrop from 'editorjs-drag-drop';
import Undo from 'editorjs-undo';
import BudgetBlock from '../editorjs/plugins/budget-block';
import CommentTune from '../editorjs/plugins/comment-tune';
import LinkTool from '../editorjs/plugins/link-tool';
import ObjectiveBlock from '../editorjs/plugins/objective-block';
import PersonaBlock from '../editorjs/plugins/persona-block';
import ResizableImage from '../editorjs/plugins/resizable-image';
import ResizableTune from '../editorjs/plugins/ResizableTune';
import VideoEmbed from '../editorjs/plugins/video-embed';
import VideoUpload from '../editorjs/plugins/video-upload';
import VideoEmbedResizableTune from '../editorjs/plugins/VideoEmbedResizableTune';
import { VIDEO_VALIDATION_CONFIG } from '../editorjs/video-validation.js';

// Initialize Day.js with plugins
dayjs.extend(relativeTime);


export default function documentEditor(livewireState, uploadUrl, canEdit, saveCallback = null, autosaveIntervalSeconds = 60, initialUpdatedAt = null, toolsConfig = null, allowedTools = null) {
    return {
        editor: null,
        state: livewireState,
        currentLocale: null,
        direction: 'ltr',
        canEdit: canEdit,
        saveCallback: saveCallback,
        autosaveInterval: autosaveIntervalSeconds * 1000, // Convert to milliseconds
        isSaving: false,
        saveStatus: null, // 'success', 'error', null
        lastSavedTime: null,
        currentEditorTime: null,
        lastSaved: null,
        currentTime: new Date(),
        isEditorBusy: false,
        justSaved: false,
        showNavigationModal: false,
        navigationUrl: null,
        navigationActiveTab: 'save',
        updateTimer: null,
        autosaveTimer: null,
        debounceTimer: null,
        isSticky: false,
        cachedTopbarHeight: 80,
        editorReady: false,
        isInitializing: false,

        // Event listener references for cleanup
        editorBusyHandler: null,
        editorFreeHandler: null,
        beforeUnloadHandler: null,
        livewireNavigatingHandler: null,
        resizeHandler: null,
        get isDirty() {

            // Compare current editor time with last saved time

            return this.currentEditorTime !== this.lastSavedTime;
        },

        get currentStatus() {
            return {
                isDirty: this.isDirty,
                isSaving: this.isSaving,
                saveStatus: this.saveStatus,
                statusText: this.isSaving ? this.statusTranslations?.saving || 'Saving...' :
                    this.saveStatus === 'success' ? this.statusTranslations?.saved || 'Saved' :
                        this.saveStatus === 'error' ? this.statusTranslations?.save_failed || 'Save failed' :
                            this.isDirty ? this.statusTranslations?.unsaved_changes || 'Unsaved changes' :
                                this.statusTranslations?.no_changes || 'No changes'
            };
        },

        get topbarHeight() {
            return this.cachedTopbarHeight;
        },

        updateTopbarHeight() {
            const topbar = document.querySelector('.fi-topbar');
            this.cachedTopbarHeight = topbar ? topbar.offsetHeight : 80;
        },


        init() {
            // Initialize timing from current state - both start the same
            try {
                const normalizedState = this.normalizeState(this.state);
                const initialStateTime = normalizedState.time;
                this.lastSavedTime = initialStateTime;
                this.currentEditorTime = initialStateTime;
            } catch (e) {
                console.error('Failed to parse initial state:', e.message, 'State:', this.state);
                const now = Date.now();
                this.lastSavedTime = now;
                this.currentEditorTime = now;
            }

            // Detect current locale and direction
            this.detectLocaleAndDirection();

            this.lastSaved = initialUpdatedAt ? new Date(initialUpdatedAt) : null;
            this.updateTopbarHeight();
            this.startUpdateTimer();
            this.initializeEditor();
            this.watchStateChanges();
            this.setupEventListeners();
            this.setupEditorBusyListeners();
            this.startAutosave();
        },

        detectLocaleAndDirection() {
            // Get current locale from Laravel app
            const htmlElement = document.documentElement;
            const bodyElement = document.body;

            // Try to get locale from various sources
            this.currentLocale = htmlElement.lang ||
                bodyElement.getAttribute('data-locale') ||
                'en';

            // Determine if the current locale requires RTL
            const rtlLocales = ['ar', 'he', 'fa', 'ur', 'ku', 'dv'];
            const isRtl = rtlLocales.includes(this.currentLocale.split('-')[0]);

            // Set direction for RTL support
            this.direction = isRtl ? 'rtl' : 'ltr';

            // Get tool translations from Laravel's translation data
            this.toolTranslations = this.getToolTranslations();
            this.uiTranslations = this.getUITranslations();
            this.statusTranslations = this.getStatusTranslations();

            // Set Day.js locale
            dayjs.locale(this.currentLocale.split('-')[0]);

            // console.log('EditorJS Localization Debug:');
            // console.log('- HTML lang attribute:', htmlElement.lang);
            // console.log('- Detected locale:', this.currentLocale);
            // console.log('- Direction:', this.direction);
            // console.log('- Tool translations:', this.toolTranslations);
            // console.log('- UI translations:', this.uiTranslations);
        },

        getToolTranslations() {
            // Try to get translations from Laravel's localization data
            // This assumes Laravel passes the translations to the frontend
            if (typeof window.Laravel !== 'undefined' && window.Laravel.translations) {
                return window.Laravel.translations.editor_tools || {};
            }

            // Fallback translations based on detected locale
            const translations = {
                'en': {
                    'paragraph': 'Text',
                    'header': 'Heading',
                    'images': 'Image',
                    'table': 'Table',
                    'nestedList': 'List',
                    'alert': 'Alert',
                    'linkTool': 'Link',
                    'objective': 'Objective',
                    'budget': 'Budget',
                    'persona': 'Persona',
                    'videoEmbed': 'Video Embed',
                    'videoUpload': 'Video Upload',
                    'commentTune': 'Add Comment',
                    'resizableTune': 'Resize'
                },
                'ar': {
                    'paragraph': 'نص',
                    'header': 'عنوان',
                    'images': 'صورة',
                    'table': 'جدول',
                    'nestedList': 'قائمة',
                    'alert': 'تنبيه',
                    'linkTool': 'رابط',
                    'objective': 'هدف',
                    'budget': 'ميزانية',
                    'persona': 'شخصية',
                    'videoEmbed': 'تضمين فيديو',
                    'videoUpload': 'رفع فيديو',
                    'commentTune': 'إضافة تعليق',
                    'resizableTune': 'تغيير الحجم'
                }
            };

            const locale = this.currentLocale.split('-')[0];
            return translations[locale] || translations['en'];
        },

        getUITranslations() {
            // Try to get UI translations from Laravel's localization data
            if (typeof window.Laravel !== 'undefined' && window.Laravel.translations) {
                return window.Laravel.translations.editor_ui || {};
            }

            // Fallback UI translations based on detected locale using EditorJS format
            const uiTranslations = {
                'en': {
                    ui: {
                        "blockTunes": {
                            "toggler": {
                                "Click to tune": "Click to tune",
                                "or drag to move": "or drag to move"
                            },
                        },
                        "inlineToolbar": {
                            "converter": {
                                "Convert to": "Convert to"
                            }
                        },
                        "toolbar": {
                            "toolbox": {
                                "Add": "Add",
                                "Filter": "Filter",
                                "Nothing found": "Nothing found"
                            }
                        },
                        "popover": {
                            "Filter": "Filter",
                            "Nothing found": "Nothing found",
                        }
                    },
                    toolNames: {
                        "Text": "Text",
                        "Heading": "Heading",
                        "List": "List",
                        "Table": "Table",
                        "Link": "Link",
                        "Objective": "Objective",
                        "Bold": "Bold",
                        "Italic": "Italic"
                    },
                    blockTunes: {
                        "delete": {
                            "Delete": "Delete"
                        },
                        "moveUp": {
                            "Move up": "Move up"
                        },
                        "moveDown": {
                            "Move down": "Move down"
                        },
                        "commentTune": {
                            "Add Comment": "Add Comment",
                            "Comment": "Comment",
                            "Add a comment": "Add a comment"
                        },
                        "resizableTune": {
                            "Resize Video": "Resize Video",
                            "Toggle resize mode": "Toggle resize mode"
                        },
                        "videoEmbedResizableTune": {
                            "Resize Video": "Resize Video",
                            "Toggle resize mode": "Toggle resize mode"
                        }
                    }
                },
                'ar': {
                    ui: {
                        "blockTunes": {
                            "toggler": {
                                "Click to tune": "انقر للضبط",
                                "or drag to move": "أو اسحب للتحريك"
                            },
                        },
                        "inlineToolbar": {
                            "converter": {
                                "Convert to": "تحويل إلى"
                            }
                        },
                        "toolbar": {
                            "toolbox": {
                                "Add": "إضافة",
                                "Filter": "تصفية",
                                "Nothing found": "لا يوجد شيء"
                            }
                        },
                        "popover": {
                            "Filter": "تصفية",
                            "Nothing found": "لا يوجد شيء",
                        }
                    },
                    toolNames: {
                        "Text": "نص",
                        "Heading": "عنوان",
                        "List": "قائمة",
                        "Table": "جدول",
                        "Link": "رابط",
                        "Objective": "هدف",
                        "Bold": "عريض",
                        "Italic": "مائل"
                    },
                    blockTunes: {
                        "delete": {
                            "Delete": "حذف"
                        },
                        "moveUp": {
                            "Move up": "تحريك لأعلى"
                        },
                        "moveDown": {
                            "Move down": "تحريك لأسفل"
                        },
                        "commentTune": {
                            "Add Comment": "إضافة تعليق",
                            "Comment": "تعليق",
                            "Add a comment": "إضافة تعليق"
                        },
                        "resizableTune": {
                            "Resize Video": "تغيير حجم الفيديو",
                            "Toggle resize mode": "تبديل وضع تغيير الحجم"
                        },
                        "videoEmbedResizableTune": {
                            "Resize Video": "تغيير حجم الفيديو",
                            "Toggle resize mode": "تبديل وضع تغيير الحجم"
                        }
                    }
                }
            };

            const locale = this.currentLocale.split('-')[0];
            return uiTranslations[locale] || uiTranslations['en'];
        },

        getStatusTranslations() {
            // Try to get status translations from Laravel's localization data
            if (typeof window.Laravel !== 'undefined' && window.Laravel.translations) {
                return window.Laravel.translations.document?.editor || {};
            }

            // Fallback status translations based on detected locale
            const statusTranslations = {
                'en': {
                    'saving': 'Saving...',
                    'saved': 'Saved',
                    'save_failed': 'Save failed',
                    'unsaved_changes': 'Unsaved changes',
                    'no_changes': 'No changes'
                },
                'ar': {
                    'saving': 'جاري الحفظ...',
                    'saved': 'محفوظ',
                    'save_failed': 'فشل الحفظ',
                    'unsaved_changes': 'تغييرات غير محفوظة',
                    'no_changes': 'لا توجد تغييرات'
                }
            };

            const locale = this.currentLocale.split('-')[0];
            return statusTranslations[locale] || statusTranslations['en'];
        },

        watchStateChanges() {
            this.$watch('state', (newState) => {
                if (newState === null && this.editor) {
                    this.editor.blocks.clear();
                    return;
                }
            });
        },

        setupEditorBusyListeners() {
            // Create bound event handlers for cleanup
            this.editorBusyHandler = () => {
                this.isEditorBusy = true;
                // Pause autosave while editor is busy
                if (this.autosaveTimer) {
                    clearInterval(this.autosaveTimer);
                    this.autosaveTimer = null;
                }
            };

            this.editorFreeHandler = () => {
                const wasBusy = this.isEditorBusy;
                this.isEditorBusy = false;
                console.log('Editor free event received - wasBusy:', wasBusy, 'isDirty:', this.isDirty, 'isSaving:', this.isSaving);

                // Don't auto-save on editor:free if we just saved (justSaved flag indicates recent save)
                if (this.justSaved) {
                    console.log('Editor free - skipping save due to recent save, restarting autosave');
                    this.startAutosave();
                    return;
                }

                // If editor was busy (plugin operation), only save if actually dirty
                // Plugin should have already triggered its own save via blockAPI.dispatchChange()
                if (wasBusy && this.isDirty && this.saveCallback && !this.isSaving) {
                    console.log('Editor free - saving due to dirty state after plugin operation');
                    this.saveDocument();
                } else if (this.isDirty && this.saveCallback && !this.isSaving) {
                    console.log('Editor free - triggering save due to dirty state');
                    this.saveDocument();
                } else {
                    console.log('Editor free - no save needed, restarting autosave');
                    this.startAutosave();
                }
            };

            // Listen for editor busy/free events from plugins
            document.addEventListener('editor:busy', this.editorBusyHandler);
            document.addEventListener('editor:free', this.editorFreeHandler);
        },

        initializeEditor() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const initialData = this.normalizeState(this.state);

            // Track editor readiness and initialization state
            this.editorReady = false;
            this.isInitializing = true;

            const editorConfig = {
                holder: 'editor-wrap',
                data: initialData,
                readOnly: !this.canEdit,
                placeholder: 'Let`s write an awesome story!',
                defaultBlock: false, // Disable default block during initialization
                inlineToolbar: false, // Disable during initialization
                tools: this.getEditorTools(csrf, uploadUrl),
                i18n: {
                    /**
                     * Text direction
                     */
                    direction: this.direction,
                    /**
                     * UI translations
                     */
                    messages: this.uiTranslations
                }, // Add RTL support and UI translations based on detected locale
                onChange: (api, event) => {
                    // Defensive check - ensure editor exists and is ready
                    if (!this.editor || !this.editorReady || this.isInitializing) {
                        console.log('Editor onChange fired but editor not ready yet, ignoring');
                        return;
                    }

                    // Skip onChange during plugin operations (like image uploads)
                    if (this.isEditorBusy) {
                        console.log('Editor onChange fired during plugin operation, ignoring');
                        return;
                    }

                    console.log('Editor onChange triggered');

                    // Update state immediately without calling save()
                    clearTimeout(this.debounceTimer);
                    this.debounceTimer = setTimeout(() => {
                        // Double-check editor still exists and is not busy
                        if (!this.editor || this.isInitializing || this.isEditorBusy) {
                            return;
                        }

                        // Only update currentEditorTime if we didn't just save
                        if (!this.justSaved) {
                            this.currentEditorTime = Date.now();
                            console.log('Editor onChange - Updated currentEditorTime:', this.currentEditorTime);
                        } else {
                            // Reset the flag after skipping the update
                            this.justSaved = false;
                            console.log('Editor onChange - Skipped update due to justSaved flag');
                        }

                        this.saveStatus = null; // Reset status on change

                        // Mark as dirty for autosave
                        // The actual save() will be called by autosave or manual save
                    }, 100);
                },
                onReady: () => {
                    this.editor.isReady?.then(() => {
                        // Mark editor as ready
                        this.editorReady = true;

                        // EditorJS automatically adds --rtl classes when direction: 'rtl' is set in i18n config

                        // Enable inline toolbar now that editor is ready
                        if (this.editor.configuration) {
                            this.editor.configuration.inlineToolbar = ['bold', 'link', 'convertTo'];
                            this.editor.configuration.defaultBlock = 'paragraph';
                        }

                        // If editor is empty, add a default paragraph block
                        if (this.editor.blocks.getBlocksCount() === 0) {
                            this.editor.blocks.insert('paragraph', {}, {}, 0, true);
                        }

                        const undo = new Undo({ editor: this.editor });
                        new DragDrop(this.editor);
                        undo.initialize(initialData);

                        // Set initialization complete after a longer delay to ensure mobile layout events are properly setup
                        setTimeout(() => {
                            this.isInitializing = false;
                        }, 300);
                    }).catch((e) => {
                        console.error('Editor.js failed to initialize:', e);
                        this.isInitializing = false; // Ensure we don't get stuck in initializing state
                    });
                }
            };

            // console.log('EditorJS Configuration:', editorConfig);
            // console.log('EditorJS i18n config:', editorConfig.i18n);

            this.editor = new EditorJS(editorConfig);
        },

        getEditorTools(csrf, uploadUrl) {
            // If toolsConfig is provided, use it to build the tools dynamically
            if (toolsConfig && typeof toolsConfig === 'object') {
                return this.buildToolsFromConfig(toolsConfig, csrf, uploadUrl, allowedTools);
            }

            // Fallback to hardcoded tools for backward compatibility
            return {
                paragraph: {
                    class: Paragraph,
                    config: { preserveBlank: true },
                    tunes: ['commentTune'],
                    toolbox: {
                        title: this.toolTranslations?.paragraph || 'Text'
                    }
                },
                header: {
                    class: Header,
                    config: {
                        placeholder: 'Enter a heading',
                        // levels: [2, 3, 4, 5],
                        // defaultLevel: 2,
                    },
                    tunes: ['commentTune'],
                    toolbox: {
                        title: this.toolTranslations?.header || 'Heading'
                    }
                },

                // image: {
                //     class: ImageTool,
                //     config: {
                //         endpoints: {
                //             byFile: uploadUrl,
                //             byUrl: uploadUrl,
                //         },
                //         additionalRequestHeaders: {
                //             'X-CSRF-TOKEN': csrf,
                //         },
                //         types: 'image/png, image/jpeg, image/jpg',
                //     }
                // },
                images: {
                    class: ResizableImage,
                    config: {
                        endpoints: {
                            byFile: uploadUrl,
                            byUrl: uploadUrl,
                            delete: '/delete-image',
                        },
                        additionalRequestHeaders: {
                            'X-CSRF-TOKEN': csrf,
                        },
                        types: 'image/*', // This will be overridden by config from PHP if available
                        field: 'image',
                        captionPlaceholder: 'Enter image caption...',
                        buttonContent: 'Select an image',
                        maxFileSize: 10485760, // 10MB default - will be overridden by config from PHP if available
                    },
                    tunes: ['commentTune'],
                    toolbox: {
                        title: this.toolTranslations?.images || 'Image'
                    }
                },
                table: {
                    class: Table,
                    withHeadings: true,
                    config: {
                        rows: 2,
                        cols: 3,
                    },
                    tunes: ['commentTune'],
                    toolbox: {
                        title: this.toolTranslations?.table || 'Table'
                    }
                },
                nestedList: {
                    class: EditorJsList,
                    config: {
                        defaultStyle: 'unordered',
                        placeholder: "Add an item",
                        maxLevel: 2,
                    },
                    tunes: ['commentTune'],
                    toolbox: {
                        title: this.toolTranslations?.nestedList || 'List'
                    }
                },
                alert: {
                    class: Alert,
                    shortcut: 'CMD+SHIFT+A',
                    config: {
                        alertTypes: ['primary', 'secondary', 'info', 'success', 'warning', 'danger', 'light', 'dark'],
                        defaultType: 'primary',
                        messagePlaceholder: 'Enter something',
                    },
                    tunes: ['commentTune'],
                    toolbox: {
                        title: this.toolTranslations?.alert || 'Alert'
                    }
                },
                linkTool: {
                    class: LinkTool,
                    config: {
                        endpoint: '/editor/fetch-url',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                        },
                    },
                    tunes: ['commentTune'],
                    toolbox: {
                        title: this.toolTranslations?.linkTool || 'Link'
                    }
                },
                videoUpload: {
                    class: VideoUpload,
                    config: {
                        endpoints: {
                            byFile: '/upload-video',
                            delete: '/delete-video'
                        },
                        additionalRequestHeaders: {
                            'X-CSRF-TOKEN': csrf,
                        },
                        types: 'video/*',
                        field: 'video',
                        maxFileSize: VIDEO_VALIDATION_CONFIG.maxFileSize,
                        chunkSize: VIDEO_VALIDATION_CONFIG.chunkSize,
                        useChunkedUpload: VIDEO_VALIDATION_CONFIG.useChunkedUpload
                    },
                    tunes: ['commentTune'],
                    toolbox: {
                        title: this.toolTranslations?.videoUpload || 'Video Upload'
                    }
                },
                commentTune: CommentTune,
                resizableTune: ResizableTune,
                videoEmbedResizableTune: VideoEmbedResizableTune
            };
        },

        buildToolsFromConfig(toolsConfig, csrf, uploadUrl, allowedTools = null) {
            const tools = {};

            console.log('Building tools from config:', toolsConfig);
            console.log('Allowed tools for toolbox:', allowedTools);

            // Class name mapping from PHP to JavaScript imports
            const classMap = {
                'paragraph': Paragraph,
                'header': Header,
                'ResizableImage': ResizableImage,
                'Table': Table,
                'EditorJsList': EditorJsList,
                'Alert': Alert,
                'LinkTool': LinkTool,
                'ObjectiveBlock': ObjectiveBlock,
                'BudgetBlock': BudgetBlock,
                'PersonaBlock': PersonaBlock,
                'VideoEmbed': VideoEmbed,
                'VideoUpload': VideoUpload
            };

            // Process each tool from the config
            Object.entries(toolsConfig).forEach(([toolName, toolConfig]) => {
                console.log(`Processing tool: ${toolName}`, toolConfig);

                const jsClass = classMap[toolConfig.class];
                if (!jsClass) {
                    console.warn(`Unknown tool class: ${toolConfig.class}`);
                    return;
                }

                // Build the tool configuration
                const tool = {
                    class: jsClass,
                    config: { ...toolConfig.config }
                };

                // Plan-based toolbox filtering: hide tool from toolbox if not in allowed list
                // but keep it available for rendering existing blocks
                if (allowedTools && !allowedTools.includes(toolName)) {
                    tool.toolbox = false; // Hide from toolbox but keep for rendering
                    console.log(`Tool ${toolName} hidden from toolbox (not in allowed tools for current plan)`);
                } else {
                    // Add localized tool title
                    if (!tool.toolbox) {
                        tool.toolbox = {};
                    }
                    if (typeof tool.toolbox === 'object' && this.toolTranslations && this.toolTranslations[toolName]) {
                        tool.toolbox.title = this.toolTranslations[toolName];
                        // console.log(`Applied localized title for ${toolName}: ${this.toolTranslations[toolName]}`);
                    }
                }

                console.log(`Built tool config for ${toolName}:`, tool);

                // Add tunes if specified
                if (toolConfig.tunes && toolConfig.tunes.length > 0) {
                    tool.tunes = toolConfig.tunes;
                }

                // Handle tools that need CSRF token injection
                if (tool.config.additionalRequestHeaders && csrf) {
                    tool.config.additionalRequestHeaders['X-CSRF-TOKEN'] = csrf;
                }

                // Handle LinkTool CSRF token
                if (toolConfig.class === 'LinkTool' && csrf) {
                    if (!tool.config.headers) {
                        tool.config.headers = {};
                    }
                    tool.config.headers['X-CSRF-TOKEN'] = csrf;
                }

                // Handle tools that need upload URL configuration
                // Only override if the endpoints are not already set from PHP config
                if (tool.config.endpoints && uploadUrl) {
                    // For ResizableImage tool, use the uploadUrl parameter
                    if (toolConfig.class === 'ResizableImage') {
                        if (tool.config.endpoints.byFile) {
                            tool.config.endpoints.byFile = uploadUrl;
                        }
                        if (tool.config.endpoints.byUrl) {
                            tool.config.endpoints.byUrl = uploadUrl;
                        }
                    }
                    // For other tools like VideoUpload, preserve the endpoints from PHP config
                    // They already have the correct routes set
                }

                // Handle VideoUpload chunk configuration from server
                if (toolConfig.class === 'VideoUpload' && toolConfig.config.chunkConfig) {
                    // Merge chunk configuration from server
                    Object.assign(tool.config, toolConfig.config.chunkConfig);
                    console.log(`VideoUpload chunk config applied:`, toolConfig.config.chunkConfig);
                }

                tools[toolName] = tool;
            });

            // Always add commentTune, resizableTune and videoEmbedResizableTune
            tools.commentTune = CommentTune;
            tools.resizableTune = ResizableTune;
            tools.videoEmbedResizableTune = VideoEmbedResizableTune;

            console.log('Final tools configuration:', tools);
            return tools;
        },

        normalizeState(state) {
            try {
                // Handle different input formats
                let parsed;
                if (typeof state === 'string') {
                    parsed = JSON.parse(state);
                } else if (typeof state === 'object' && state !== null) {
                    parsed = state;
                } else {
                    throw new Error('Invalid state format');
                }

                // If we have valid blocks array, return normalized state with sanitized blocks
                if (parsed && Array.isArray(parsed.blocks)) {
                    return this.deepCloneEditorState(parsed);
                }

                // If we have blocks but no time, add time
                if (parsed && parsed.blocks && !parsed.time) {
                    return this.deepCloneEditorState({
                        ...parsed,
                        time: Date.now(),
                        version: parsed.version || '2.31.0-rc.7'
                    });
                }

            } catch (e) {
                console.warn('Invalid EditorJS state', e, 'State:', state);
            }

            // Fallback to empty state
            return {
                time: Date.now(),
                blocks: [],
                version: '2.31.0-rc.7',
            };
        },

        /**
         * Deep clone EditorJS state data safely for structuredClone compatibility
         * Specifically handles nestedList blocks that may contain non-cloneable objects
         */
        deepCloneEditorState(state) {
            const cloned = {
                time: state.time || Date.now(),
                blocks: [],
                version: state.version || '2.31.0-rc.7'
            };

            if (Array.isArray(state.blocks)) {
                cloned.blocks = state.blocks.map(block => this.sanitizeBlockData(block));
            }

            return cloned;
        },

        /**
         * Sanitize block data to ensure compatibility with structuredClone
         * Removes non-cloneable objects like Proxy instances
         */
        sanitizeBlockData(block) {
            const sanitized = {
                id: block.id,
                type: block.type,
                data: {}
            };

            if (block.data) {
                // Handle nestedList blocks specifically
                if (block.type === 'nestedList') {
                    sanitized.data = this.sanitizeNestedListData(block.data);
                } else {
                    // For other block types, use regular deep cloning
                    sanitized.data = this.deepCloneObject(block.data);
                }
            }

            // Preserve other block properties
            if (block.tunes) {
                sanitized.tunes = this.deepCloneObject(block.tunes);
            }

            return sanitized;
        },

        /**
         * Sanitize nestedList data structure to prevent DataCloneError
         */
        sanitizeNestedListData(data) {
            const sanitized = {
                style: data.style || 'unordered',
                meta: Array.isArray(data.meta) ? [] : { ...data.meta },
                items: []
            };

            if (Array.isArray(data.items)) {
                sanitized.items = data.items.map(item => this.sanitizeListItem(item));
            }

            return sanitized;
        },

        /**
         * Recursively sanitize list items in nestedList
         */
        sanitizeListItem(item) {
            const sanitized = {
                content: typeof item.content === 'string' ? item.content : '',
                meta: Array.isArray(item.meta) ? [] : { ...item.meta },
                items: []
            };

            if (Array.isArray(item.items)) {
                sanitized.items = item.items.map(subItem => this.sanitizeListItem(subItem));
            }

            return sanitized;
        },

        /**
         * Safe deep clone implementation that handles most data types
         */
        deepCloneObject(obj) {
            if (obj === null || typeof obj !== 'object') {
                return obj;
            }

            if (obj instanceof Date) {
                return new Date(obj.getTime());
            }

            if (Array.isArray(obj)) {
                return obj.map(item => this.deepCloneObject(item));
            }

            const cloned = {};
            for (const key in obj) {
                if (obj.hasOwnProperty(key)) {
                    try {
                        cloned[key] = this.deepCloneObject(obj[key]);
                    } catch (e) {
                        // Skip non-cloneable properties
                        console.warn(`Skipping non-cloneable property: ${key}`, e);
                    }
                }
            }

            return cloned;
        },

        setupEventListeners() {
            // Create bound event handlers for cleanup
            this.beforeUnloadHandler = (e) => {
                if (this.isEditorBusy || this.isDirty) {
                    const message = this.isEditorBusy
                        ? 'Page is processing. You may lose unsaved data if you leave now.'
                        : 'You have unsaved changes that will be lost.';
                    e.preventDefault();
                    e.returnValue = message; // For older browsers
                    return message;
                }
            };

            this.livewireNavigatingHandler = (e) => {
                if (this.isEditorBusy || (this.isDirty && this.saveCallback)) {
                    e.preventDefault();

                    // Store navigation URL and show modal
                    this.navigationUrl = e.detail.url || e.target.href;
                    this.showNavigationModal = true;
                    this.navigationActiveTab = this.isEditorBusy ? 'cancel' : 'save';

                    return false;
                }
            };

            this.resizeHandler = () => {
                this.updateTopbarHeight();
            };

            // Add event listeners
            window.addEventListener('beforeunload', this.beforeUnloadHandler);
            document.addEventListener('livewire:navigating', this.livewireNavigatingHandler);
            window.addEventListener('resize', this.resizeHandler);
        },

        // Navigation modal methods
        closeNavigationModal() {
            this.showNavigationModal = false;
            this.navigationUrl = null;
            this.navigationActiveTab = 'save';
        },

        async saveAndClose() {
            try {
                await this.saveDocument();
                window.location.href = this.navigationUrl;
                this.closeNavigationModal();
            } catch (error) {
                console.error('Save failed during navigation:', error);
                // Keep modal open and show error
                this.saveStatus = 'error';
            }
        },

        discardAndClose() {
            window.location.href = this.navigationUrl;
            this.closeNavigationModal();
        },

        startAutosave() {
            if (!this.saveCallback || !this.autosaveInterval) return;

            this.autosaveTimer = setInterval(() => {
                if (this.isDirty && !this.isSaving && !this.isEditorBusy) {
                    this.saveDocument();
                }
            }, this.autosaveInterval);
        },

        restartAutosave() {
            if (this.autosaveTimer) {
                clearInterval(this.autosaveTimer);
            }
            this.startAutosave();
        },

        async saveDocument() {
            if (!this.saveCallback || this.isSaving || !this.editorReady || !this.editor) return;

            // Check if document is empty
            const parsedState = this.normalizeState(this.state);
            if (!parsedState.blocks || parsedState.blocks.length === 0) {
                console.warn('Document is empty, skipping save');
                return;
            }


            this.isSaving = true;
            this.saveStatus = null;

            // Pause autosave while saving
            if (this.autosaveTimer) {
                clearInterval(this.autosaveTimer);
                this.autosaveTimer = null;
            }

            try {
                const outputData = await this.editor.save();
                this.state = outputData;

                await this.saveCallback(JSON.stringify(this.state));

                this.saveStatus = 'success';

                // Get the time from the state that was just saved
                const savedStateTime = this.state.time;
                this.lastSaved = new Date(savedStateTime); // Convert to Date object for formatLastSaved()
                this.lastSavedTime = savedStateTime;
                this.currentEditorTime = savedStateTime; // Set both to same value after save
                this.justSaved = true; // Flag to prevent onChange from overriding this

                this.restartUpdateTimer(); // Restart timer from the new save time

                // Clear success status after 3 seconds
                setTimeout(() => {
                    if (this.saveStatus === 'success') {
                        this.saveStatus = null;
                    }
                }, 3000);

            } catch (error) {
                console.error('Save failed:', error);
                this.saveStatus = 'error';

                // Clear error status after 5 seconds
                setTimeout(() => {
                    if (this.saveStatus === 'error') {
                        this.saveStatus = null;
                    }
                }, 5000);
            } finally {
                this.isSaving = false;
                // Always restart autosave whether success or error
                this.startAutosave();
            }
        },

        formatLastSaved() {
            if (!this.lastSaved) return '';

            // Use Day.js for proper internationalization
            return dayjs(this.lastSaved).fromNow();
        },

        startUpdateTimer() {
            // Update the time display every 10 seconds
            this.updateTimer = setInterval(() => {
                this.currentTime = new Date();
            }, 10000);
        },

        restartUpdateTimer() {
            // Clear existing timer and start a new one
            if (this.updateTimer) {
                clearInterval(this.updateTimer);
            }
            this.startUpdateTimer();
        },

        destroy() {
            // Reset initialization state
            this.isInitializing = false;
            this.editorReady = false;

            // Cleanup timers
            if (this.autosaveTimer) {
                clearInterval(this.autosaveTimer);
                this.autosaveTimer = null;
            }
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = null;
            }
            if (this.updateTimer) {
                clearInterval(this.updateTimer);
                this.updateTimer = null;
            }

            // Cleanup event listeners
            if (this.editorBusyHandler) {
                document.removeEventListener('editor:busy', this.editorBusyHandler);
            }
            if (this.editorFreeHandler) {
                document.removeEventListener('editor:free', this.editorFreeHandler);
            }
            if (this.beforeUnloadHandler) {
                window.removeEventListener('beforeunload', this.beforeUnloadHandler);
            }
            if (this.livewireNavigatingHandler) {
                document.removeEventListener('livewire:navigating', this.livewireNavigatingHandler);
            }
            if (this.resizeHandler) {
                window.removeEventListener('resize', this.resizeHandler);
            }

            // Cleanup editor safely
            if (this.editor && typeof this.editor.destroy === 'function') {
                try {
                    // Wait for editor to be fully ready before destroying to avoid event cleanup issues
                    if (this.editor.isReady) {
                        this.editor.isReady.then(() => {
                            this.editor.destroy();
                            this.editor = null;
                        }).catch((e) => {
                            console.warn('Editor destroy failed:', e);
                            this.editor = null;
                        });
                    } else {
                        // Editor not ready yet, just set to null
                        this.editor = null;
                    }
                } catch (e) {
                    console.warn('Error during editor cleanup:', e);
                    this.editor = null;
                }
            }
        }
    }
}



