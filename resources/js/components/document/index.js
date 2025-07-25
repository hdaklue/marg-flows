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
            const topbar = document.querySelector('.fi-topbar');
            return topbar ? topbar.offsetHeight : 80;
        },


        init() {

            // Initialize timing from current state - both start the same
            try {
                const parsedState = JSON.parse(this.state);
                const initialStateTime = parsedState.time;
                this.lastSavedTime = initialStateTime;
                this.currentEditorTime = initialStateTime;
            } catch (e) {
                console(e.message)
                const now = Date.now();
                this.lastSavedTime = now;
                this.currentEditorTime = now;
            }

            this.lastSaved = initialUpdatedAt ? new Date(initialUpdatedAt) : null;
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
            // Listen for editor busy/free events from plugins
            document.addEventListener('editor:busy', () => {
                this.isEditorBusy = true;
                // Pause autosave while editor is busy
                if (this.autosaveTimer) {
                    clearInterval(this.autosaveTimer);
                    this.autosaveTimer = null;
                }
            });

            document.addEventListener('editor:free', () => {
                this.isEditorBusy = false;

                // Sync currentEditorTime with actual current state
                // try {
                //     const currentState = JSON.parse(this.state);
                //     this.currentEditorTime = currentState.time;
                // } catch (e) {
                //     console.warn('Failed to sync currentEditorTime:', e);
                // }

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
            });
        },

        initializeEditor() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const initialData = this.normalizeState(this.state);

            this.editor = new EditorJS({
                holder: 'editor-wrap',
                data: initialData,
                readOnly: !this.canEdit,
                placeholder: 'Let`s write an awesome story!',
                defaultBlock: 'paragraph',
                inlineToolbar: ['bold', 'link', 'convertTo'],
                tools: this.getEditorTools(csrf, uploadUrl),
                onChange: () => {
                    this.editor.save()
                        .then((outputData) => {

                            clearTimeout(this.debounceTimer);
                            this.debounceTimer = setTimeout(() => {
                                this.state = JSON.stringify(outputData);

                                // Only update currentEditorTime if we didn't just save
                                if (!this.justSaved) {
                                    this.currentEditorTime = outputData.time;
                                } else {
                                    // Reset the flag after skipping the update
                                    this.justSaved = false;
                                }

                                this.saveStatus = null; // Reset status on change
                            }, 100); // Reduced from 300ms to 100ms for faster UI feedback

                        });
                },

                onReady: () => {
                    this.editor.isReady?.then(() => {
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
                    inlineToolbar: true,
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
                    inlineToolbar: true,
                    config: {
                        defaultStyle: 'unordered',
                        placeholder: "Add an item",
                        maxLevel: 2,
                    },
                    tunes: ['commentTune']
                },
                alert: {
                    class: Alert,
                    inlineToolbar: true,
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
                commentTune: CommentTune
            };
        },



        normalizeState(state) {
            try {
                if (typeof state === 'string') {
                    state = JSON.parse(state);
                }

                if (state && Array.isArray(state.blocks)) {
                    return JSON.parse(JSON.stringify(state));
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
            // Save on page unload with processing check (only for refresh/close)
            window.addEventListener('beforeunload', (e) => {
                if (this.isEditorBusy || this.isDirty) {
                    const message = this.isEditorBusy
                        ? 'Page is processing. You may lose unsaved data if you leave now.'
                        : 'You have unsaved changes that will be lost.';
                    e.preventDefault();
                    e.returnValue = message; // For older browsers
                    return message;
                }
            });

            // Save on Livewire navigate with processing check
            document.addEventListener('livewire:navigating', (e) => {
                if (this.isEditorBusy || (this.isDirty && this.saveCallback)) {
                    e.preventDefault();
                    
                    // Store navigation URL and show modal
                    this.navigationUrl = e.detail.url || e.target.href;
                    this.showNavigationModal = true;
                    this.navigationActiveTab = this.isEditorBusy ? 'cancel' : 'save';
                    
                    return false;
                }
            });

        },

        // Navigation modal methods
        closeNavigationModal() {
            this.showNavigationModal = false;
            this.navigationUrl = null;
            this.navigationActiveTab = 'save';
        },

        saveAndClose() {
            this.saveDocument().then(() => {
                window.location.href = this.navigationUrl;
            });
            this.closeNavigationModal();
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
            if (!this.saveCallback || this.isSaving) return;

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
                this.editor.save().then((outputData) => {
                    this.state = outputData;
                    this.saveCallback(JSON.stringify(this.state)).then(() => {

                        this.saveStatus = 'success';

                        // Get the time from the state that was just saved
                        const savedStateTime = this.state.time;
                        this.lastSaved = new Date(savedStateTime); // Convert to Date object for formatLastSaved()
                        this.lastSavedTime = savedStateTime;
                        this.currentEditorTime = savedStateTime; // Set both to same value after save
                        this.justSaved = true; // Flag to prevent onChange from overriding this
                        // this.lastSavedTime = savedStateTime;
                        // this.currentEditorTime = savedStateTime;

                        this.restartUpdateTimer(); // Restart timer from the new save time

                        // Clear success status after 3 seconds
                        setTimeout(() => {
                            if (this.saveStatus === 'success') {
                                this.saveStatus = null;
                            }
                        }, 3000);
                    })
                })



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
            }
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }
            if (this.updateTimer) {
                clearInterval(this.updateTimer);
            }
        }
    }
}



