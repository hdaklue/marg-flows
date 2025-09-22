import EditorJS from "@editorjs/editorjs";
import Header from "@editorjs/header";
import LinkTool from './plugins/link-tool';
import EditorJsList from "@editorjs/list";
import Paragraph from "@editorjs/paragraph";
import Table from "@editorjs/table";
import Alert from 'editorjs-alert';
import DragDrop from 'editorjs-drag-drop';
import Undo from 'editorjs-undo';
import ResizableImage from './plugins/resizable-image';
import ObjectiveBlock from './plugins/objective-block';


export default function editorjs(livewireState, uploadUrl, canEdit) {
    return {
        editor: null,
        state: livewireState,
        currentLocale: null,
        editorLocale: null,
        canEdit: !canEdit,

        init() {

            this.initializeEditor();
            this.watchStateChanges();
        },

        watchStateChanges() {
            this.$watch('state', (newState) => {
                if (newState === null && this.editor) {
                    this.editor.blocks.clear();
                    return;
                }
            });
        },

        initializeEditor() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const initialData = this.normalizeState(this.state);
            // If state was empty/null, sync the normalized data back to Livewire
            if (!this.state || this.state === 'null') {
                this.state = JSON.stringify(initialData);
            }

            console.log(this.canEdit);

            this.editor = new EditorJS({
                holder: 'editor-wrap',
                data: initialData,
                readOnly: this.canEdit,
                placeholder: 'Let`s write an awesome story!',
                defaultBlock: 'paragraph',
                inlineToolbar: ['bold', 'link', 'convertTo'],
                tools: this.getEditorTools(csrf, uploadUrl),
                onChange: () => {
                    this.editor.save()
                        .then((outputData) => {
                            this.state = JSON.stringify(outputData);
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
                },
                header: {
                    class: Header,
                    config: {
                        placeholder: 'Enter a heading',
                        // levels: [2, 3, 4, 5],
                        defaultLevel: 2,
                    },
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
                    }
                },
                table: {
                    class: Table,
                    withHeadings: true,
                    config: {
                        rows: 2,
                        cols: 3,
                    },
                },
                nestedList: {
                    class: EditorJsList,
                    inlineToolbar: true,
                    config: {
                        defaultStyle: 'unordered',
                        placeholder: "Add an item",
                        maxLevel: 2,
                    }
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
                },
                linkTool: {
                    class: LinkTool,
                    config: {
                        endpoint: '/editor/fetch-url',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                        },
                    }
                },
                objective: {
                    class: ObjectiveBlock,
                    inlineToolbar: false,
                    config: {
                        placeholder: 'Enter objective name...',
                    }
                },
            };
        },



        normalizeState(state) {

            try {
                if (typeof state === 'string') {
                    state = JSON.parse(state);
                }

                if (state && Array.isArray(state.blocks)) {
                    return this.deepCloneEditorState(state);
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
        }
    }
}



