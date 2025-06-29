import { EditorJS } from "@editorjs/editorjs";

document.addEventListener('alpine:init', () => {
    Alpine.data('editorJs', (state) => (
        {
            editor: null,
            state: state,

            init() {
                const initialData = {};

                this.editor = new EditorJS({
                    holder: 'editor-wrap',
                    data: initialData,
                    tools: {
                        checkList: {
                            class: Checklist,
                            config: {
                                placeholder: 'Add an item',
                            },
                        },
                        header: {
                            class: Header,
                            config: {
                                placeholder: 'Enter a heading',
                                levels: [2, 3, 4],
                                defaultLevel: 2,
                                inlineToolbar: true,
                            },
                        },
                        table: {
                            class: Table,
                            config: {
                                rows: 2,
                                cols: 3,
                            },
                        },
                    },

                    onReady: () => {
                        console.log('Editor.js ready');
                    },
                });
            },

            async save() {
                if (this.editor) {
                    await this.editor.isReady;
                    const output = await this.editor.save();
                    this.state = output;
                    console.log('Saved:', output);
                }
            },

            normalizeState(state) {
                try {
                    if (typeof state === 'string') {
                        state = JSON.parse(state);
                    }

                    return Array.isArray(state?.blocks) ?
                        state : {
                            blocks: [{
                                type: 'paragraph',
                                data: {
                                    text: 'Start writing...',
                                },
                            }],
                        };
                } catch {
                    return {
                        blocks: [{
                            type: 'paragraph',
                            data: {
                                text: 'Start writing...',
                            },
                        }],
                    };
                }
            },
        }
    ))
})

