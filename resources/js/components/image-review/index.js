
export default function designReviewApp() {
    function uuidv4() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0,
                v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    return {
        isOpen: false,
        currentImage: '',
        designId: null,
        comments: [],
        showingComment: false,
        activeCommentId: null,
        showCommentPopup: false,
        showConfirmDialog: false,
        hasUnsavedChanges: false,
        isSelecting: false,
        isDragging: false,
        allCommentsHidden: false,
        _openedViaEvent: false,
        isSaving: false, // Add loading state
        selectionStart: {
            x: 0,
            y: 0,
            xPx: null,
            yPx: null
        },
        selectionBox: {
            x: 0,
            y: 0,
            width: 0,
            height: 0
        },
        newComment: null,
        popupAnchor: {
            x: 0,
            y: 0
        },
        selectedCommentIds: [],
        showCommentFilter: false,
        visibleComments: [],
        filterMode: false,
        touchStartTime: 0,
        longPressTimer: null,
        touchMoved: false,
        callbacks: {
            onSaveComment: null,
            onDeleteComment: null,
            onEditComment: null,
            onCommentClick: null,
            onModalOpen: null,
            onModalClose: null
        },

        get commentPopupStyle() {
            if (!this.popupAnchor || !this.$refs.imageContainer) return '';
            const rect = this.$refs.imageContainer.getBoundingClientRect();
            const popupWidth = 300,
                popupHeight = 180;
            let left = this.popupAnchor.x - rect.left - popupWidth / 2;
            let top = (this.popupAnchor.y + 10) - rect.top;
            left = Math.max(10, Math.min(left, rect.width - popupWidth - 10));
            top = Math.max(10, Math.min(top, rect.height - popupHeight - 10));
            return `top: ${top}px; left: ${left}px;`;
        },

        get hasActiveFilter() {
            if (this.allCommentsHidden) return false;
            return this.selectedCommentIds.length < this.comments.length;
        },

        get hasValidComment() {
            return this.newComment?.text?.trim() !== '';
        },

        get canSave() {
            return this.hasValidComment && !this.isSaving;
        },

        init(callbacks = {}) {
            if (callbacks && Object.keys(callbacks).length > 0) {
                this.setCallbacks(callbacks);
            }
            this.$watch('selectedCommentIds', () => {
                if (this.filterMode) {
                    this.updateVisibleComments();
                }
            });

            // Register event listener for opening modal
            window.addEventListener('open-design-review', (event) => {
                this._openedViaEvent = true;
                const {
                    imageUrl,
                    comments,
                    designId
                } = event.detail;

                this.openModal(imageUrl, comments || [], {
                    onSaveComment: async (comment, image) => {
                        if (window.Livewire) {
                            // Return promise for async operations
                            return new Promise((resolve, reject) => {
                                Livewire.emit('saveComment', {
                                    designId: designId,
                                    comment: comment
                                });
                                // You might want to listen for Livewire response
                                resolve();
                            });
                        }
                    },
                    onDeleteComment: async (commentId) => {
                        if (window.Livewire) {
                            return new Promise((resolve, reject) => {
                                Livewire.emit('deleteComment', {
                                    designId: designId,
                                    commentId: commentId
                                });
                                resolve();
                            });
                        }
                    },
                    onCommentClick: (comment) => {
                        console.log('Comment clicked:', comment);
                    }
                });
            });




        },
        destroy() {
            window.removeEventListener('open-design-review', this.handleOpenEvent);
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
            }
        },

        handleEscape() {
            if (this.showingComment) {
                this.closeComment();
                return;
            }
            if (!this.showConfirmDialog) {
                this.handleClose();
                return;

            }
            if (this.showConfirmDialog) {
                this.handleCancelConfirmationDialog();

                return;
            }

        },

        updateVisibleComments() {
            this.visibleComments = this.filterMode ?
                this.comments.filter(c => this.selectedCommentIds.includes(c.id)) :
                this.comments.filter(c => c.text?.trim());

        },

        toggleCommentFilter() {
            this.showCommentFilter = !this.showCommentFilter;
            this.filterMode = !this.filterMode;
        },

        toggleAllComments() {
            if (this.showCommentPopup) return;
            this.allCommentsHidden ? this.showAllComments() : this.hideAllComments();
        },

        hideAllComments() {
            this.selectedCommentIds = [];
            this.visibleComments = [];
            this.allCommentsHidden = true;
        },

        showAllComments() {
            this.visibleComments = this.comments;
            this.selectedCommentIds = this.comments.map(c => c.id);
            this.allCommentsHidden = false;
        },

        openModal(imageUrl, existingComments = [], designId) {
            if (this._openedViaEvent) {
                console.warn(
                    'Design Review: Modal opened via both direct method and event. Using direct method parameters.'
                );
                this._openedViaEvent = false;
            }

            this.currentImage = imageUrl;
            this.designId = designId;

            this.comments = existingComments;
            this.selectedCommentIds = this.comments.map(c => c.id);

            // Update callbacks if provided
            // if (callbacks && Object.keys(callbacks).length > 0) {
            //     this.setCallbacks(callbacks);
            // }

            this.isOpen = true;
            this.hasUnsavedChanges = false;
            this.isSaving = false;
            this.updateVisibleComments();

            if (this.callbacks.onModalOpen) {
                this.callbacks.onModalOpen(this.currentImage);
            }
        },

        closeModal() {
            this.isOpen = false;
            this.reset();
            if (this.callbacks.onModalClose) {
                this.callbacks.onModalClose();
            }
        },

        handleCancelConfirmationDialog() {
            this.showConfirmDialog = false;
            this.$nextTick(() => this.$refs.commentTextarea?.focus());
        },

        reset() {
            this.comments = [];
            this.selectedCommentIds = [];
            this.activeCommentId = null;
            this.showCommentPopup = false;
            this.hasUnsavedChanges = false;
            this.isSelecting = false;
            this.isDragging = false;
            this.allCommentsHidden = false;
            this.newComment = null;
            this.visibleComments = [];
            this.filterMode = false;
            this.showCommentFilter = false;
            this.isSaving = false;
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
        },
        handleClose() {
            if (this.isSaving) {
                return;
            }

            // Simplified empty comment check
            if (this.showCommentPopup) {
                const hasText = this.newComment?.text && this.newComment.text.trim().length > 0;

                if (!hasText) {
                    this.showCommentPopup = false;
                    this.newComment = null;
                    return;
                }

                if (this.hasUnsavedChanges) {
                    this.showConfirmDialog = true;
                    return;
                }
            }

            if (this.showCommentFilter) {
                this.showCommentFilter = false;
                this.filterMode = false;
                return;
            }

            this.closeModal();
        },

        handleConfirmCloseConfirmationDialog() {
            this.showConfirmDialog = false;
            if (this.showCommentPopup) {
                this.showCommentPopup = false;
                this.newComment = null;
                return;
            }

        },

        handleBackdropClick(event) {
            if (event.target === event.currentTarget) this.handleClose();
        },

        // Mouse Events
        startSelection(event) {
            if (event.button !== 0 || this.showCommentPopup) return;
            const rect = this.$refs.imageContainer.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            this.selectionStart = {
                x: (x / rect.width) * 100,
                y: (y / rect.height) * 100,
                xPx: x,
                yPx: y
            };
            this.isDragging = true;
            this.isSelecting = false;
        },

        updateSelection: debounce(function (event) {
            if (!this.isDragging || !this.selectionStart.xPx) return;
            const rect = this.$refs.imageContainer.getBoundingClientRect();
            const x = Math.max(0, Math.min(event.clientX - rect.left, rect.width));
            const y = Math.max(0, Math.min(event.clientY - rect.top, rect.height));
            const distance = Math.sqrt(Math.pow(x - this.selectionStart.xPx, 2) + Math.pow(y - this.selectionStart
                .yPx, 2));
            if (distance > 5) this.isSelecting = true;
            if (!this.isSelecting) return;

            const xPercent = (x / rect.width) * 100;
            const yPercent = (y / rect.height) * 100;
            this.selectionBox = {
                x: Math.min(this.selectionStart.x, xPercent),
                y: Math.min(this.selectionStart.y, yPercent),
                width: Math.abs(xPercent - this.selectionStart.x),
                height: Math.abs(yPercent - this.selectionStart.y)
            };
        }, 16),

        endSelection(event) {
            if (!this.isDragging) return;
            const rect = this.$refs.imageContainer.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            this.isDragging = false;

            if (this.isSelecting && this.selectionBox.width > 1 && this.selectionBox.height > 1) {
                this.createAreaComment();
            } else if (x >= 0 && x <= rect.width && y >= 0 && y <= rect.height) {
                this.handleClick(event.clientX, event.clientY, rect);
            }

            this.resetSelectionState();
        },

        // Touch Events
        handleTouchStart(event) {
            if (this.showCommentPopup) return;
            const touch = event.touches[0];
            const rect = this.$refs.imageContainer.getBoundingClientRect();
            const x = touch.clientX - rect.left;
            const y = touch.clientY - rect.top;

            this.touchStartTime = Date.now();
            this.touchMoved = false;
            this.selectionStart = {
                x: (x / rect.width) * 100,
                y: (y / rect.height) * 100,
                xPx: x,
                yPx: y
            };
            this.isDragging = true;
            this.isSelecting = false;

            // Long press for area selection on mobile
            this.longPressTimer = setTimeout(() => {
                if (!this.touchMoved && this.isDragging) {
                    navigator.vibrate && navigator.vibrate(50);
                    this.isSelecting = true;
                }
            }, 500);
        },

        handleTouchMove(event) {
            if (!this.isDragging) return;
            const touch = event.touches[0];
            const rect = this.$refs.imageContainer.getBoundingClientRect();
            const x = Math.max(0, Math.min(touch.clientX - rect.left, rect.width));
            const y = Math.max(0, Math.min(touch.clientY - rect.top, rect.height));

            const distance = Math.sqrt(
                Math.pow(x - this.selectionStart.xPx, 2) +
                Math.pow(y - this.selectionStart.yPx, 2)
            );

            if (distance > 10) {
                this.touchMoved = true;
                if (this.longPressTimer) {
                    clearTimeout(this.longPressTimer);
                    this.longPressTimer = null;
                }
                this.isSelecting = true;
            }

            if (!this.isSelecting) return;

            const xPercent = (x / rect.width) * 100;
            const yPercent = (y / rect.height) * 100;
            this.selectionBox = {
                x: Math.min(this.selectionStart.x, xPercent),
                y: Math.min(this.selectionStart.y, yPercent),
                width: Math.abs(xPercent - this.selectionStart.x),
                height: Math.abs(yPercent - this.selectionStart.y)
            };
        },

        handleTouchEnd(event) {
            if (!this.isDragging) return;

            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }

            const rect = this.$refs.imageContainer.getBoundingClientRect();
            const touchDuration = Date.now() - this.touchStartTime;

            if (this.isSelecting && this.selectionBox.width > 1 && this.selectionBox.height > 1) {
                this.createAreaComment();
            } else if (!this.touchMoved && touchDuration < 500) {
                // It's a tap
                const touch = event.changedTouches[0];
                this.handleClick(touch.clientX, touch.clientY, rect);
            }

            this.isDragging = false;
            this.resetSelectionState();
        },

        // Common handlers
        handleClick(clientX, clientY, rect) {
            const x = clientX - rect.left;
            const y = clientY - rect.top;
            const xPercent = (x / rect.width) * 100;
            const yPercent = (y / rect.height) * 100;

            if (this.isClickInsideNewComment(xPercent, yPercent)) return;

            const isMobile = 'ontouchstart' in window;

            const searchRadius = isMobile ? 2.2 : 1.5; // 2% padding on mobile
            const clickedComment = this.findCommentAtPoint(xPercent, yPercent, searchRadius);
            if (clickedComment) {
                return this.selectComment(clickedComment);
            }

            this.newComment = {
                text: '',
                x: xPercent - 1,
                y: yPercent - 1,
                width: 2,
                height: 2,
                type: 'point'
            };
            this.popupAnchor = {
                x: clientX,
                y: clientY
            };
            this.showCommentPopup = true;
            this.hasUnsavedChanges = true;
            this.$nextTick(() => this.$refs.commentTextarea?.focus());
        },

        createAreaComment() {
            this.newComment = {
                text: '',
                x: this.selectionBox.x,
                y: this.selectionBox.y,
                width: this.selectionBox.width,
                height: this.selectionBox.height,
                type: 'area'
            };
            const rect = this.$refs.imageContainer.getBoundingClientRect();
            const anchorX = this.selectionBox.x + this.selectionBox.width / 2;
            const anchorY = this.selectionBox.y + this.selectionBox.height;
            this.popupAnchor = {
                x: rect.left + (anchorX * rect.width / 100),
                y: rect.top + (anchorY * rect.height / 100)
            };
            this.showCommentPopup = true;
            this.hasUnsavedChanges = true;
            this.$nextTick(() => this.$refs.commentTextarea?.focus());
        },

        cancelSelection() {
            this.isDragging = false;
            this.isSelecting = false;
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
            this.resetSelectionState();
        },

        resetSelectionState() {
            this.selectionStart = {
                x: 0,
                y: 0,
                xPx: null,
                yPx: null
            };
            this.selectionBox = {
                x: 0,
                y: 0,
                width: 0,
                height: 0
            };
            this.isSelecting = false;
        },

        isClickInsideNewComment(xPercent, yPercent) {
            if (!this.newComment) return false;
            const c = this.newComment;
            return xPercent >= c.x && xPercent <= c.x + c.width && yPercent >= c.y && yPercent <= c.y + c.height;
        },

        findCommentAtPoint(x, y, searchRadius = 0) {
            return this.visibleComments.find(c => {
                const expandedLeft = c.x - searchRadius;
                const expandedRight = c.x + c.width + searchRadius;
                const expandedTop = c.y - searchRadius;
                const expandedBottom = c.y + c.height + searchRadius;

                return x >= expandedLeft && x <= expandedRight &&
                    y >= expandedTop && y <= expandedBottom;
            });
        },

        selectComment(comment) {

            this.showingComment = true;
            this.activeCommentId = comment.id;
            this.$wire.set('activeCommentId', comment.id);

        },

        closeComment() {
            this.showingComment = false;
            this.activeCommentId = null;
            this.$wire.set('activeCommentId', null);
        },
        async saveComment() {
            if (!this.newComment?.text?.trim() || this.isSaving) return;

            this.isSaving = true;

            try {
                const comment = {
                    id: uuidv4(),
                    ...this.newComment,
                    author: 'Current User',
                    timestamp: new Date().toISOString(),
                    resolved: false
                };

                // Add to local state first for immediate UI feedback



                // Call the async callback if it exists
                if (this.callbacks.onSaveComment) {
                    await this.callbacks.onSaveComment(comment, this.designId, this.currentImage);
                }


                // Close popup after successful save
                this.showCommentPopup = false;
                this.hasUnsavedChanges = false;
                this.newComment = null;
                this.updateVisibleComments();


            } catch (error) {
                console.error('Error saving comment:', error);

                // Rollback on error
                this.comments = this.comments.filter(c => c.id !== comment.id);
                this.selectedCommentIds = this.selectedCommentIds.filter(id => id !== comment.id);
                this.updateVisibleComments();

                // You might want to show an error message to the user
                alert('Failed to save comment. Please try again.');

            } finally {
                this.isSaving = false;
            }
        },

        cancelComment() {
            if (this.canSave) {
                this.showConfirmDialog = true;
                return;
            }
            this.showCommentPopup = false;
            this.hasUnsavedChanges = false;
            this.newComment = null;
        },

        // Public API methods
        setCallbacks(callbacks = {}) {
            const validCallbacks = ['onSaveComment', 'onDeleteComment', 'onEditComment', 'onCommentClick', 'onModalOpen', 'onModalClose'];

            Object.keys(callbacks).forEach(key => {
                if (validCallbacks.includes(key) && typeof callbacks[key] === 'function') {
                    this.callbacks[key] = callbacks[key];
                }
            });
        },

        getComments() {
            return this.comments;
        },

        addComment(comment) {
            this.comments.push({
                id: comment.id || uuidv4(),
                ...comment
            });
            this.selectedCommentIds.push(comment.id);
            this.updateVisibleComments();
        },

        async removeComment(commentId) {
            try {
                // Remove from local state first
                const originalComments = [...this.comments];
                const originalSelectedIds = [...this.selectedCommentIds];

                this.comments = this.comments.filter(c => c.id !== commentId);
                this.selectedCommentIds = this.selectedCommentIds.filter(id => id !== commentId);
                this.updateVisibleComments();

                // Call the async callback if it exists
                if (this.callbacks.onDeleteComment) {
                    await this.callbacks.onDeleteComment(commentId);
                }

            } catch (error) {
                console.error('Error deleting comment:', error);

                // Rollback on error
                this.comments = originalComments;
                this.selectedCommentIds = originalSelectedIds;
                this.updateVisibleComments();

                alert('Failed to delete comment. Please try again.');
            }
        }
    };
}
