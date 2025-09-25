<?php

declare(strict_types=1);

namespace App\Livewire\Feedback;

use App\Enums\Feedback\FeedbackUrgency;
use Livewire\Attributes\Locked;
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

    public ?string $comment = null;

    public ?string $commentText = null;

    #[Locked]
    public array $mentionables = [];

    #[Locked]
    public array $hashables = [];

    public array $currentMentions = [];

    public array $currentHashtags = [];

    public array $voiceNoteUrls = [];

    public bool $hasVoiceNotes = false;

    public bool $hasUnuploadedVoiceNotes = false;

    // public $listeners = [
    //     'voice-note:canceled' => '$refresh',
    // ];

    public int|string|null $urgency = null;

    #[Renderless]
    public function hasUnuploadedNotes()
    {
        $this->dispatch('voice-recorder:check-status');

        return false;
    }

    #[Renderless]
    #[On('mentionable:text')]
    public function updateCommentText(string $state): void
    {
        $this->commentText = $state;
        logger()->info($state);
    }

    public function getCanSaveProperty()
    {
        return ! empty(trim($this->commentText)) || $this->hasVoiceNotes;
    }

    public function mount()
    {
        $this->setupSampleData();
    }

    #[On('open-comment-modal')]
    public function openCommentModal($commentData = [])
    {
        $this->pendingComment = $commentData ?: [];
        $this->currentMentions = [];
        $this->currentHashtags = [];
        $this->voiceNoteUrls = [];
        $this->hasVoiceNotes = false;
        $this->hasUnuploadedVoiceNotes = false;
        $this->urgency = FeedbackUrgency::SUGGESTION->value;
        $this->showCommentModal = true;
    }

    public function clear()
    {
        $this->clearData();
        $this->setupSampleData();
    }

    public function saveNewComment()
    {
        dd($this->urgency);
        // Check for unuploaded voice notes first
        if ($this->hasUnuploadedVoiceNotes) {
            $this->addError('form', 'Please wait for voice notes to finish uploading.');

            return;
        }

        $this->validate();

        // Additional check using computed property
        if (! $this->canSave) {
            $this->addError('form', 'Please add a comment or voice note.');

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

        // Clear all data using single source of truth
        $this->clearData();

        // Dispatch comment created event
        $this->dispatch('feedback-modal:comment-created', [
            'success' => true,
            'comment' => $comment,
            'message' => 'Comment saved successfully',
        ]);
    }

    #[On('feedback-modal:empty-cancel')]
    public function onCancelEmptyFeedback()
    {
        logger()->info('onCancelEmptyFeedback called');
        $this->clearData();
    }

    #[Renderless]
    public function isDirty(): bool
    {
        return
            ! empty($this->commentText)
            || ! empty($this->pendingComment)
            || $this->hasUnuploadedNotes
            || $this->hasVoiceNotes;
    }

    public function cancelComment()
    {
        // Check if there are unsaved changes
        $hasUnsavedChanges =
            ! empty(trim($this->commentText))
            || $this->hasVoiceNotes
            || $this->hasUnuploadedVoiceNotes;

        logger()->info('cancelComment called', [
            'commentText' => $this->commentText,
            'hasVoiceNotes' => $this->hasVoiceNotes,
            'hasUnuploadedVoiceNotes' => $this->hasUnuploadedVoiceNotes,
            'hasUnsavedChanges' => $hasUnsavedChanges,
        ]);

        if ($hasUnsavedChanges) {
            logger()->info('Dispatching confirmation event');
            $this->dispatch('feedback-modal:show-cancel-confirmation');
            logger()->info('After dispatch, commentText is: ' . $this->commentText);

            return;
        }

        logger()->info('No unsaved changes, calling performCancel');
        $this->urgency = FeedbackUrgency::NORMAL->value;
        logger()->info('urgency', [$this->urgency]);

        // $this->performCancel();
    }

    // #[On('confirm-cancel')]
    public function handleConfirmCancel()
    {
        logger()->info('handleConfirmCancel called');

        // Dispatch events to clean up any active recordings or players first
        $this->dispatch('voice-note:canceled');

        // Clear all data using single source of truth
        $this->clearData();
    }

    #[On('voice-note:uploaded')]
    public function onVoiceNoteUploaded($url)
    {
        $this->voiceNoteUrls[] = $url;
        $this->hasVoiceNotes = ! empty($this->voiceNoteUrls);
        $this->hasUnuploadedVoiceNotes = false;
    }

    #[On('voice-note:removed')]
    public function onVoiceNoteRemoved($index)
    {
        if (isset($this->voiceNoteUrls[$index])) {
            array_splice($this->voiceNoteUrls, $index, 1);
            $this->voiceNoteUrls = array_values($this->voiceNoteUrls);
        }
        $this->hasVoiceNotes = ! empty($this->voiceNoteUrls);
    }

    #[On('voice-note:recording-started')]
    #[Renderless]
    public function onVoiceNoteRecordingStarted()
    {
        $this->hasUnuploadedVoiceNotes = true;
    }

    #[On('voice-note:recording-stopped')]
    #[Renderless]
    public function onVoiceNoteRecordingStopped()
    {
        $this->hasUnuploadedVoiceNotes = false;
    }

    #[Renderless]
    #[On('mentionable:mention-added')]
    public function addCurrentMention($id)
    {
        if (! in_array($id, $this->currentMentions)) {
            $this->currentMentions[] = $id;
        }
        logger()->info('mentiond', $this->currentMentions);
    }

    #[Renderless]
    #[On('mentionable:hash-added')]
    public function addCurrentHashtag($id)
    {
        if (! in_array($id, $this->currentHashtags)) {
            $this->currentHashtags[] = $id;
        }
        logger()->info('hashed', $this->currentHashtags);
    }

    #[Renderless]
    public function removeCurrentMention($mentionId)
    {
        $this->currentMentions = array_values(array_filter(
            $this->currentMentions,
            fn ($id) => $id !== $mentionId,
        ));
    }

    #[Renderless]
    public function removeCurrentHashtag($hashtagId)
    {
        $this->currentHashtags = array_values(array_filter(
            $this->currentHashtags,
            fn ($id) => $id !== $hashtagId,
        ));
    }

    public function render()
    {
        return view('livewire.feedback.create-feedback-modal');
    }

    protected function rules()
    {
        return [
            'commentText' => 'required_without:hasVoiceNotes|string|min:1',
            'hasVoiceNotes' => 'required_without:commentText|boolean',
        ];
    }

    protected function messages()
    {
        return [
            'commentText.required_without' => 'Please add a comment or voice note.',
            'hasVoiceNotes.required_without' => 'Please add a comment or voice note.',
        ];
    }

    private function clearData()
    {
        logger()->info('clearData called - resetting all modal data');
        $this->pendingComment = [];
        $this->commentText = '';
        $this->currentMentions = [];
        $this->currentHashtags = [];
        $this->voiceNoteUrls = [];
        $this->hasVoiceNotes = false;
        $this->hasUnuploadedVoiceNotes = false;
        $this->urgency = FeedbackUrgency::NORMAL->value;
        $this->showCommentModal = false;
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
                'id' => '2',
                'name' => 'urgent',
                'url' => 'https://example.com/tags/urgent',
            ],
            [
                'id' => '3',
                'name' => 'design',
                'url' => 'https://example.com/tags/design',
            ],
            [
                'id' => '4',
                'name' => 'bug',
                'url' => 'https://example.com/tags/bug',
            ],
            [
                'id' => '5',
                'name' => 'feature',
                'url' => 'https://example.com/tags/feature',
            ],
            [
                'id' => '6',
                'name' => 'review',
                'url' => 'https://example.com/tags/review',
            ],
        ];
    }
}
