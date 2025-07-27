import Tribute from "tributejs";
export default function mentionableText(mentions, hashables) {
    return {
        // Component state
        content: '',
        contentModel: '',
        tribute: null,
        mentionables: JSON.stringify(mentions),
        hashables: JSON.stringify(hashables),
        isInitialized: false,

        // Initialize component
        init() {
            console.log(this.mentionables);

            // Only setup if not already initialized
            if (!this.isInitialized) {
                this.setupTribute();
                this.isInitialized = true;
            }
        },

        // Setup Tribute.js with mentions and hashtags using collections
        setupTribute() {
            const textareaId = this.$refs.textarea ? this.$refs.textarea.id : 'unknown';
            // console.log(`MentionableText[${textareaId}]: setupTribute called`);
            // console.log(`MentionableText[${textareaId}]: mentionables`, this.mentionables);
            // console.log(`MentionableText[${textareaId}]: hashables`, this.hashables);

            if (!this.$refs.textarea) {
                console.warn(`MentionableText[${textareaId}]: textarea ref not found`);
                return;
            }

            // Check if Tribute is already attached to prevent double binding
            if (this.$refs.textarea.tribute || this.$refs.textarea.hasAttribute('data-tribute')) {
                console.warn(`MentionableText[${textareaId}]: Tribute already attached to this textarea`);
                return;
            }

            // Build collections array for Tribute
            const collections = [];
            console.log(`MentionableText[${textareaId}]: Building collections...`);

            // Add mentions collection if mentionables are provided
            if (this.mentionables && this.mentionables.length > 0) {
                console.log(collections)
                console.log('MentionableText: Adding mentions collection');
                collections.push({
                    trigger: '@',
                    values: JSON.parse(this.mentionables),
                    lookup: 'name',
                    fillAttr: 'email',
                    selectTemplate: (item) => {
                        return `<span contenteditable="false"><span class="text-xs px-2 py-1 bg-sky-800 text-sky-100 rounded-md border border-sky-700 dark:bg-sky-700 dark:text-sky-100 dark:border-sky-600">@${item.original.name}</span></span>`;
                    },
                    menuItemTemplate: (item) => {
                        const data = item.original;
                        const avatar = data.avatar
                            ? `<img src="${data.avatar}" class="tribute-item-avatar" alt="${data.name}">`
                            : `<div class="tribute-item-avatar-placeholder">${data.name.charAt(0).toUpperCase()}</div>`;

                        const title = data.title ? `<div class="tribute-item-title">${data.title}</div>` : '';

                        return `<div style="display: flex; align-items: center; gap: 0.5rem;">${avatar}<div class="tribute-item-content"><div class="tribute-item-name">${item.string}</div><div class="tribute-item-email">${data.email}</div>${title}</div></div>`;
                    },
                    searchOpts: {
                        pre: '<span>',
                        post: '</span>',
                        skip: false
                    },
                    requireLeadingSpace: false,
                    allowSpaces: false,
                    menuItemLimit: 8,
                    menuShowMinLength: 1
                });
            }
            console.log('first collection', collections)
            // this.tribute = new Tribute({
            //     collection: collections,
            //     menuContainer: document.querySelector('.mentionable-text-container'),
            //     spaceSelectsMatch: true,
            //     positionMenu: true
            // });
            // this.tribute.attach(this.$refs.textarea);
            // this.$refs.textarea.setAttribute('data-tribute', 'true');
            // Add hashtags collection if hashables are provided
            if (this.hashables && this.hashables.length > 0) {
                console.log('MentionableText: Adding hashtags collection');
                collections.push({
                    trigger: '#',
                    values: JSON.parse(this.hashables),
                    lookup: 'name',
                    fillAttr: 'name',
                    selectTemplate: (item) => {
                        return `<span contenteditable="false"><a class="text-xs px-2 py-1 bg-indigo-800 text-indigo-100 rounded-md border border-indigo-700 dark:bg-indigo-700 dark:text-indigo-100 dark:border-indigo-600 no-underline" href='${item.original.url}'>#${item.original.name}</a></span>`;
                    },
                    menuItemTemplate: (item) => {
                        const data = item.original;
                        return `<div style="display: flex; align-items: center; gap: 0.5rem;"><div class="tribute-item-hashtag-icon">#</div><div class="tribute-item-content"><div class="tribute-item-name">${item.string}</div>${data.url ? `<div class="tribute-item-url">${data.url}</div>` : ''}</div></div>`;
                    },
                    searchOpts: {
                        pre: '<span>',
                        post: '</span>',
                        skip: false
                    },
                    requireLeadingSpace: false,
                    allowSpaces: false,
                    menuItemLimit: 8,
                    menuShowMinLength: 1,
                    autocompleteMode: true,
                });
            }
            // console.log('is it ebent work?');
            // // Only initialize Tribute if we have collections
            // console.log('MentionableText: Collections built:', collections);
            // if (collections.length > 0) {
            //     console.log('MentionableText: Initializing Tribute with', collections.length, 'collections');
            //     console.log(collections);
            this.tribute = new Tribute({
                collection: collections,
                menuContainer: document.body,
                spaceSelectsMatch: true,
                positionMenu: true
            });

            //     console.log('MentionableText: Attaching Tribute to textarea');
            this.tribute.attach(this.$refs.textarea);
            this.$refs.textarea.setAttribute('data-tribute', 'true');

            // Listen for tribute events to trigger Alpine reactivity and update Livewire arrays
            this.$refs.textarea.addEventListener('tribute-replaced', (e) => {
                const item = e.detail.item;
                
                // Update Livewire arrays based on mention type
                if (item.original) {
                    if (e.detail.event.key === '@' || item.original.email) {
                        // This is a mention - add to currentMentions
                        this.$wire.call('addCurrentMention', item.original.id);
                    } else if (e.detail.event.key === '#' || item.original.url) {
                        // This is a hashtag - add to currentHashtags
                        this.$wire.call('addCurrentHashtag', item.original.id);
                    }
                }
                
                // Trigger Alpine reactivity by dispatching input event
                this.$refs.textarea.dispatchEvent(new Event('input', { bubbles: true }));
            });

            //     // Listen for tribute events to update content
            //     this.$refs.textarea.addEventListener('tribute-replaced', (e) => {
            //         console.log('MentionableText: tribute-replaced event', e);
            //         this.content = e.target.value;
            //         this.updateContent();
            //     });

            //     console.log('MentionableText: Tribute setup complete');
            // } else {
            //     console.warn('MentionableText: No collections to initialize');
            // }

            this.setupTributeStyles();
        },

        // Setup custom styles for Tribute menu (now handled by CSS file)
        setupTributeStyles() {
            // Styles are now loaded via CSS file - no need for inline styles
        },

        // Update content and sync with wire:model
        updateContent() {
            if (this.contentModel && this.$wire) {
                this.$wire.set(this.contentModel, this.content);
            }
        },

        // Sync contenteditable with Alpine data
        syncContent() {
            if (this.$refs.textarea) {
                this.content = this.$refs.textarea.innerHTML;
                this.updateContent();
            }
        },

        // Set mentionables data
        // setMentionables(mentionables) {
        //     console.log('MentionableText: setMentionables called with', mentionables);
        //     this.mentionables = mentionables || [];
        // },

        // Set hashables data
        // setHashables(hashables) {
        //     console.log('MentionableText: setHashables called with', hashables);
        //     this.hashables = hashables || [];
        // },

        // Initialize tribute after data is set
        initializeWithData() {
            console.log('MentionableText: initializeWithData called');
            console.log('MentionableText: mentionables length:', this.mentionables.length);
            console.log('MentionableText: hashables length:', this.hashables.length);

            if (!this.isInitialized) {
                this.setupTribute();
                this.isInitialized = true;
            }
        },

        // Reinitialize Tribute when data changes
        reinitializeTribute() {
            // Only reinitialize if not already initialized
            if (!this.isInitialized) {
                return;
            }

            // Clean up existing instance
            if (this.tribute) {
                this.tribute.detach(this.$refs.textarea);
                this.tribute = null;
            }

            // Clear the textarea's tribute reference
            if (this.$refs.textarea) {
                this.$refs.textarea.tribute = null;
                this.$refs.textarea.removeAttribute('data-tribute');
            }

            this.setupTribute();
        },

        // Get mentioned users from content
        getMentionedUsers() {
            const mentionRegex = /@(\w+)/g;
            const mentions = [];
            let match;

            while ((match = mentionRegex.exec(this.content)) !== null) {
                const mentionedName = match[1];
                const user = this.mentionables.find(m => m.name === mentionedName);
                if (user && !mentions.some(m => m.id === user.id)) {
                    mentions.push(user);
                }
            }

            return mentions;
        },

        // Get hashtags from content
        getHashtags() {
            const hashtagRegex = /#(\w+)/g;
            const hashtags = [];
            let match;

            while ((match = hashtagRegex.exec(this.content)) !== null) {
                const hashtagName = match[1];
                const hashtag = this.hashables.find(h => h.name === hashtagName);
                if (hashtag && !hashtags.some(h => h.name === hashtag.name)) {
                    hashtags.push(hashtag);
                }
            }

            return hashtags;
        },

        // Clean up when component is destroyed
        destroy() {
            if (this.tribute) {
                this.tribute.detach(this.$refs.textarea);
                this.tribute = null;
            }

            // Clear the textarea's tribute reference
            if (this.$refs.textarea) {
                this.$refs.textarea.tribute = null;
                this.$refs.textarea.removeAttribute('data-tribute');
            }
        }
    };
}
