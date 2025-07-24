/**
 * Comment Block Tune for EditorJS
 * Adds comment functionality to any block
 */
class CommentTune {
    static get isTune() {
        return true;
    }

    constructor({ api, data, config, block }) {
        this.api = api;
        this.data = data || {};
        this.config = config || {};
        this.block = block;
        this.wrapper = null;
        
        // Generate unique block ID if not exists
        if (!this.data.blockId) {
            this.data.blockId = this.generateBlockId();
        }
    }

    generateBlockId() {
        return 'block_' + Date.now() + '_' + Math.random().toString(36).substring(2, 11);
    }

    render() {
        return {
            icon: this.getCommentIcon(),
            label: 'Comments',
            onActivate: () => {
                this.openComments();
            },
            closeOnActivate: true
        };
    }

    getCommentIcon() {
        return `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                      d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
            </svg>
        `;
    }

    openComments() {
        console.log('CommentTune: Opening comments for block', this.data.blockId);
        
        // Dispatch custom event for Livewire to handle
        const event = new CustomEvent('editorjs:document-view-comments', {
            detail: {
                blockId: this.data.blockId,
                blockType: this.block.name,
                blockIndex: this.api.blocks.getCurrentBlockIndex()
            },
            bubbles: true
        });
        
        console.log('CommentTune: Dispatching event', event);
        document.dispatchEvent(event);
    }


    save() {
        return {
            blockId: this.data.blockId
        };
    }

    // Called when block is removed - dispatch cleanup event
    destroy() {
        const event = new CustomEvent('editorjs:document-block-removed', {
            detail: {
                blockId: this.data.blockId,
                blockType: this.block.name
            },
            bubbles: true
        });
        
        document.dispatchEvent(event);
    }
}

export default CommentTune;