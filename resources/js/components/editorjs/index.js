import EditorJS from "@editorjs/editorjs";
import Header from "@editorjs/header";
import ImageTool from '@editorjs/image';
import EditorJsList from "@editorjs/list";
import Paragraph from "@editorjs/paragraph";
import Table from "@editorjs/table";
import ChangeCase from 'editorjs-change-case';
import DragDrop from 'editorjs-drag-drop';

import HyperLink from 'editorjs-hyperlink';
import Undo from 'editorjs-undo';

document.addEventListener('alpine:init', () => {
    Alpine.data('editorJs', (livewireState, uploadUrl) => ({
        editor: null,
        state: livewireState,
        currentLocale: null,
        editorLocale: null,

        init() {

            this.setupLocaleWatcher();
            this.initializeEditor();
            this.watchStateChanges();
        },

        setupLocaleWatcher() {
            window.addEventListener('locale-changed', (e) => {
                if (e.detail?.locale && e.detail.locale !== this.currentLocale) {
                    this.currentLocale = e.detail.locale;
                }
            });
        },

        watchStateChanges() {
            this.$watch('state', (newState) => {
                if (newState === null && this.editor) {

                    this.editor.blocks.clear();
                    return;
                }
                if (this.editor && this.editorLocale !== this.currentLocale) {
                    const renderedState = (newState === "" || newState === null) ? '{}' : newState;
                    this.editor.render(this.normalizeState(renderedState));
                    this.editorLocale = this.currentLocale;
                }
            });
        },

        initializeEditor() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const initialData = this.normalizeState(this.state);

            this.editor = new EditorJS({
                holder: 'editor-wrap',
                data: initialData,
                placeholder: 'Let`s write an awesome story!',
                defaultBlock: 'paragraph',
                inlineToolbar: ['bold', 'link', 'changeCase', 'convertTo'],
                tools: this.getEditorTools(csrf, uploadUrl),
                i18n: this.getI18nConfig(this.currentLocale),

                onChange: () => {
                    this.editor.save().then((outputData) => {
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
                        levels: [2, 3, 4, 5],
                        defaultLevel: 2,
                    },
                    inlineToolbar: ['changeCase'],
                },
                image: {
                    class: ImageTool,
                    config: {
                        endpoints: {
                            byFile: uploadUrl,
                            byUrl: uploadUrl,
                        },
                        additionalRequestHeaders: {
                            'X-CSRF-TOKEN': csrf,
                        },
                        types: 'image/png, image/jpeg, image/jpg',
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
                hyperlink: {
                    class: HyperLink,
                    config: {
                        shortcut: 'CMD+L',
                        target: '_blank',
                        rel: 'nofollow',
                        availableTargets: ['_blank', '_self'],
                        availableRels: ['author', 'noreferrer'],
                        validate: false,
                    }
                },
                changeCase: {
                    class: ChangeCase,
                    config: {
                        showLocaleOption: true,
                        locale: ['ar-AR', 'en-EN'],
                    }
                },
            };
        },

        getI18nConfig(locale) {
            return locale === 'ar'
                ? {
                    inlineToolbar: { converter: { 'Convert to': 'تحويل إلى' } },
                    tools: { header: 'عناوين' },
                }
                : {};
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
    }));
});
