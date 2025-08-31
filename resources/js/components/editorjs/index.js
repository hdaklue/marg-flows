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
        }
    }
}



