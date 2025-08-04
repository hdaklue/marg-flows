import EditorJS from "@editorjs/editorjs";
import Header from "@editorjs/header";
import EditorJsList from "@editorjs/list";
import Paragraph from "@editorjs/paragraph";
import Table from "@editorjs/table";
import Alert from 'editorjs-alert';
import DragDrop from 'editorjs-drag-drop';
import HyperLink from 'editorjs-hyperlink';
import Undo from 'editorjs-undo';
import CommentTune from '../editorjs/plugins/comment-tune';
import ResizableImage from '../editorjs/plugins/resizable-image';
import VideoEmbed from '../editorjs/plugins/video-embed';
import VideoUpload from '../editorjs/plugins/video-upload';
import { VIDEO_VALIDATION_CONFIG } from '../editorjs/video-validation.js';


export default function documentEditor(livewireState, uploadUrl, canEdit, saveCallback = null, autosaveIntervalSeconds = 30, initialUpdatedAt = null) {
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
                const parsedState = JSON.parse(this.state);
                const initialStateTime = parsedState.time;
                this.lastSavedTime = initialStateTime;
                this.currentEditorTime = initialStateTime;
            } catch (e) {
                console.error('Failed to parse initial state:', e.message);
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
                this.isEditorBusy = false;

                // Safety save after editor operations complete, then restart autosave timer
                if (this.isDirty && this.saveCallback && !this.isSaving) {
                    this.saveDocument();
                } else {
                    // Extra safety save to ensure state is persisted
                    if (this.saveCallback && !this.isSaving) {
                        this.saveDocument();
                    }
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

            // Track editor readiness
            this.editorReady = false;

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
                    if (!this.editor || !this.editorReady) {
                        return;
                    }

                    // Update state immediately without calling save()
                    clearTimeout(this.debounceTimer);
                    this.debounceTimer = setTimeout(() => {
                        // Double-check editor still exists
                        if (!this.editor) {
                            return;
                        }

                        // Only update currentEditorTime if we didn't just save
                        if (!this.justSaved) {
                            this.currentEditorTime = Date.now();
                        } else {
                            // Reset the flag after skipping the update
                            this.justSaved = false;
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
                    }).catch((e) => {
                        console.error('Editor.js failed to initialize:', e);
                    });
                }
            });
        },

        getEditorTools(csrf, uploadUrl) {
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
                        types: 'image/*',
                        field: 'image',
                        captionPlaceholder: 'Enter image caption...',
                        buttonContent: 'Select an image',
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
                hyperlink: {
                    class: HyperLink,
                    config: {
                        shortcut: 'CMD+L',
                        target: '_blank',
                        rel: 'nofollow',
                        availableTargets: ['_blank', '_self'],
                        availableRels: ['author', 'noreferrer'],
                        validate: false,
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



        normalizeState(state) {
            try {
                const parsed = typeof state === 'string' ? JSON.parse(state) : state;

                if (parsed && Array.isArray(parsed.blocks)) {
                    return {
                        ...parsed,
                        blocks: [...parsed.blocks] // Shallow clone sufficient
                    };
                }
            } catch (e) {
                console.warn('Invalid EditorJS state', e);
            }

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

        async saveDocument(isSync = false) {
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



