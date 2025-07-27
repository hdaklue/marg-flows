<?php

declare(strict_types=1);

namespace App\Livewire\Feedback;

use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;

/**
 * Summary of CreateFeedbackModal.
 */
final class CreateFeedbackModal extends Component
{
    public bool $showCommentModal = false;

    public array $pendingComment = [];

    public string $commentText = '';

    public array $mentionables = [];

    public array $hashables = [];

    public array $currentMentions = [];

    public array $currentHashtags = [];

    public function mount()
    {
        $this->showCommentModal = false;
        $this->pendingComment = [];
        $this->commentText = '';
        $this->currentMentions = [];
        $this->currentHashtags = [];
        $this->setupSampleData();
    }

    #[On('open-comment-modal')]
    public function openCommentModal($commentData = [])
    {
        $this->pendingComment = $commentData ?: [];
        $this->commentText = '';
        $this->currentMentions = [];
        $this->currentHashtags = [];

        $this->showCommentModal = true;
    }

    public function saveNewComment()
    {
        // Allow saving if we have text
        if (empty(trim($this->commentText))) {
            return;
        }

        $comment = [
            'id' => uniqid(),
            'text' => $this->commentText,
            'x' => $this->pendingComment['x'],
            'y' => $this->pendingComment['y'],
            'width' => $this->pendingComment['width'],
            'height' => $this->pendingComment['height'],
            'type' => $this->pendingComment['type'],
            'author' => 'Current User',
            'timestamp' => now()->toISOString(),
            'resolved' => false,
            'mentions' => $this->currentMentions,
            'hashtags' => $this->currentHashtags,
        ];

        // Store design_id before resetting pendingComment
        $designId = $this->pendingComment['designId'] ?? null;

        $this->showCommentModal = false;
        $this->pendingComment = [];
        $this->commentText = '';
        $this->currentMentions = [];
        $this->currentHashtags = [];
        $this->voiceNoteUrls = [];

        // Dispatch comment created event
        $this->dispatch('comment-created', [
            'success' => true,
            'comment' => $comment,
            'message' => 'Comment saved successfully',
        ]);
    }

    public function cancelComment()
    {
        $this->showCommentModal = false;
        $this->pendingComment = [];
        $this->commentText = '';
        $this->currentMentions = [];
        $this->currentHashtags = [];
        $this->voiceNoteUrls = [];
        $this->dispatch('voice-note:canceled');
    }

    #[Renderless]
    public function addCurrentMention($mentionId)
    {
        if (! in_array($mentionId, $this->currentMentions)) {
            $this->currentMentions[] = $mentionId;
        }
        logger()->info('mentiond', $this->currentMentions);
    }

    #[Renderless]
    public function addCurrentHashtag($hashtagId)
    {
        if (! in_array($hashtagId, $this->currentHashtags)) {
            $this->currentHashtags[] = $hashtagId;
        }
    }

    #[Renderless]
    public function removeCurrentMention($mentionId)
    {
        $this->currentMentions = array_values(array_filter($this->currentMentions, fn ($id) => $id !== $mentionId));
    }

    #[Renderless]
    public function removeCurrentHashtag($hashtagId)
    {
        $this->currentHashtags = array_values(array_filter($this->currentHashtags, fn ($id) => $id !== $hashtagId));
    }

    public function render()
    {
        return view('livewire.feedback.create-feedback-modal');
    }

    private function setupSampleData()
    {
        // Sample mentionables (users)
        $this->mentionables = [
            [
                'id' => '1',
                'name' => 'اليس جونسون',
                'email' => 'alice@example.com',
                'avatar' => null,
                'title' => 'Designer',
                'department' => 'Creative',
            ],
            [
                'id' => '2',
                'name' => 'Bob Smith',
                'email' => 'bob@example.com',
                'avatar' => null,
                'title' => 'Developer',
                'department' => 'Engineering',
            ],
            [
                'id' => '3',
                'name' => 'Carol Wilson',
                'email' => 'carol@example.com',
                'avatar' => null,
                'title' => 'Product Manager',
                'department' => 'Product',
            ],
        ];

        // Sample hashables (hashtags)
        $this->hashables = [
            [
                'name' => 'urgent',
                'url' => 'https://example.com/tags/urgent',
            ],
            [
                'name' => 'design',
                'url' => 'https://example.com/tags/design',
            ],
            [
                'name' => 'bug',
                'url' => 'https://example.com/tags/bug',
            ],
            [
                'name' => 'feature',
                'url' => 'https://example.com/tags/feature',
            ],
            [
                'name' => 'review',
                'url' => 'https://example.com/tags/review',
            ],
        ];
    }
}
