import EditorJS from "@editorjs/editorjs";
import Header from "@editorjs/header";
import EditorJsList from "@editorjs/list";
import Paragraph from "@editorjs/paragraph";
import Table from "@editorjs/table";
import Alert from 'editorjs-alert';
import DragDrop from 'editorjs-drag-drop';
import LinkTool from '../editorjs/plugins/link-tool';
import Undo from 'editorjs-undo';
import CommentTune from '../editorjs/plugins/comment-tune';
import ResizableImage from '../editorjs/plugins/resizable-image';
import VideoEmbed from '../editorjs/plugins/video-embed';
import VideoUpload from '../editorjs/plugins/video-upload';
import { VIDEO_VALIDATION_CONFIG } from '../editorjs/video-validation.js';


export default function documentEditor(livewireState, uploadUrl, canEdit, saveCallback = null, autosaveIntervalSeconds = 30, initialUpdatedAt = null, toolsConfig = null, allowedTools = null) {
    return {
        editor: null,
        state: livewireState,
        currentLocale: null,
        editorLocale: null,
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
                statusText: this.isSaving ? 'Saving...' :
                    this.saveStatus === 'success' ? 'Saved' :
                        this.saveStatus === 'error' ? 'Save failed' :
                            this.isDirty ? 'Unsaved changes' : 'No changes'
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

            this.lastSaved = initialUpdatedAt ? new Date(initialUpdatedAt) : null;
            this.updateTopbarHeight();
            this.startUpdateTimer();
            this.initializeEditor();
            this.watchStateChanges();
            this.setupEventListeners();
            this.setupEditorBusyListeners();
            this.startAutosave();
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

            this.editor = new EditorJS({
                holder: 'editor-wrap',
                data: initialData,
                readOnly: !this.canEdit,
                placeholder: 'Let`s write an awesome story!',
                defaultBlock: false, // Disable default block during initialization
                inlineToolbar: false, // Disable during initialization
                tools: this.getEditorTools(csrf, uploadUrl),
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

                        // Set initialization complete after a short delay to ensure all initial rendering is done
                        setTimeout(() => {
                            this.isInitializing = false;
                        }, 100);
                    }).catch((e) => {
                        console.error('Editor.js failed to initialize:', e);
                        this.isInitializing = false; // Ensure we don't get stuck in initializing state
                    });
                }
            });
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
                    tunes: ['commentTune']
                },
                header: {
                    class: Header,
                    config: {
                        placeholder: 'Enter a heading',
                        // levels: [2, 3, 4, 5],
                        // defaultLevel: 2,
                    },
                    tunes: ['commentTune']
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
                    tunes: ['commentTune']

                },
                table: {
                    class: Table,
                    withHeadings: true,
                    config: {
                        rows: 2,
                        cols: 3,
                    },
                    tunes: ['commentTune']
                },
                nestedList: {
                    class: EditorJsList,
                    config: {
                        defaultStyle: 'unordered',
                        placeholder: "Add an item",
                        maxLevel: 2,
                    },
                    tunes: ['commentTune']
                },
                alert: {
                    class: Alert,
                    shortcut: 'CMD+SHIFT+A',
                    config: {
                        alertTypes: ['primary', 'secondary', 'info', 'success', 'warning', 'danger', 'light', 'dark'],
                        defaultType: 'primary',
                        messagePlaceholder: 'Enter something',
                    },
                    tunes: ['commentTune']
                },
                linkTool: {
                    class: LinkTool,
                    config: {
                        endpoint: '/editor/fetch-url',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                        },
                    },
                    tunes: ['commentTune']
                },
                videoEmbed: {
                    class: VideoEmbed,
                    config: {
                        placeholder: 'Paste a YouTube URL...',
                        allowDirectUrls: true
                    },
                    tunes: ['commentTune']
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
                    tunes: ['commentTune']
                },
                commentTune: CommentTune
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

                tools[toolName] = tool;
            });

            // Always add commentTune
            tools.commentTune = CommentTune;

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

                // If we have valid blocks array, return normalized state
                if (parsed && Array.isArray(parsed.blocks)) {
                    return {
                        time: parsed.time || Date.now(),
                        blocks: [...parsed.blocks], // Shallow clone sufficient
                        version: parsed.version || '2.31.0-rc.7'
                    };
                }

                // If we have blocks but no time, add time
                if (parsed && parsed.blocks && !parsed.time) {
                    return {
                        ...parsed,
                        time: Date.now(),
                        version: parsed.version || '2.31.0-rc.7'
                    };
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

            const diff = this.currentTime - this.lastSaved;
            const seconds = Math.max(0, Math.floor(diff / 1000)); // Ensure non-negative
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);

            if (seconds < 60) return `${seconds}s ago`;
            if (minutes < 60) return `${minutes}m ago`;
            if (hours < 24) return `${hours}h ago`;
            return this.lastSaved.toLocaleDateString();
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

            // Cleanup editor
            if (this.editor && this.editor.destroy) {
                this.editor.destroy();
                this.editor = null;
            }
        }
    }
}



