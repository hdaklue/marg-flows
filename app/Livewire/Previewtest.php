<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

final class Previewtest extends Component
{
    public string $image = 'https://picsum.photos/600/900?random=3';

    public string $activeCommentId = '';

    // Modal state
    public bool $showCommentModal = false;

    public array $pendingComment = [];

    public string $commentText = '';

    public $comments = [
        [
            'id' => 'c91c1dbe-3ef1-4208-a8e9-9d3f010f0c21',
            'text' => 'Adjust the spacing here.',
            'x' => 12,
            'y' => 15,
            'width' => 2,
            'height' => 2,
            'type' => 'point',
            'author' => 'Alice',
            'timestamp' => '2025-06-01T10:00:00Z',
            'resolved' => false,
        ],
        [
            'id' => 'd7517139-3f2f-453e-9436-8cb31f2fc177',
            'text' => 'Consider realigning this section.',
            'x' => 35,
            'y' => 25,
            'width' => 15,
            'height' => 10,
            'type' => 'area',
            'author' => 'Bob',
            'timestamp' => '2025-06-01T10:05:00Z',
            'resolved' => false,
        ],
    ];

    public $images = [
        [
            'id' => '1',
            // 'url' => 'https://picsum.photos/600/900?random=1',
            'url' => 'img/1.jpeg',
            'comments' => [
                [
                    'id' => 'c91c1dbe-3ef1-4208-a8e9-9d3f010f0c21',
                    'text' => 'Adjust the spacing here.',
                    'x' => 12,
                    'y' => 15,
                    'width' => 2,
                    'height' => 2,
                    'type' => 'point',
                    'author' => 'Alice',
                    'timestamp' => '2025-06-01T10:00:00Z',
                    'resolved' => false,
                ],
                [
                    'id' => 'd7517139-3f2f-453e-9436-8cb31f2fc177',
                    'text' => 'Consider realigning this section.',
                    'x' => 35,
                    'y' => 25,
                    'width' => 15,
                    'height' => 10,
                    'type' => 'area',
                    'author' => 'Bob',
                    'timestamp' => '2025-06-01T10:05:00Z',
                    'resolved' => false,
                ],
            ],
        ],
        [
            'id' => '2',
            'url' => 'img/2.png',
            'comments' => [
                [
                    'id' => 'c91c1dbe-3ef1-4208-a8e9-9d3f010f0c32',
                    'text' => 'another one',
                    'x' => 12,
                    'y' => 15,
                    'width' => 2,
                    'height' => 2,
                    'type' => 'point',
                    'author' => 'Alice',
                    'timestamp' => '2025-06-01T10:00:00Z',
                    'resolved' => false,
                ],

            ],

        ],
    ];

    public function updatedActiveCommentId($value)
    {
        logger($value);
    }

    #[On('open-comment-modal')]
    public function openCommentModal($commentData = [])
    {

        // Log the incoming data for debugging
        Log::info('Opening comment modal', ['commentData' => $commentData]);

        $this->pendingComment = $commentData ?: [];
        $this->commentText = '';
        $this->showCommentModal = true;
    }

    public function saveNewComment()
    {
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
        ];

        // Add to comments array
        $this->comments[] = $comment;

        // Close modal
        $this->showCommentModal = false;
        $this->pendingComment = [];
        $this->commentText = '';

        // Log the comment data for debugging
        Log::info('New comment created', [
            'comment' => $comment,
            'pending_comment' => $this->pendingComment,
            'design_id' => $this->pendingComment['designId'] ?? null,
        ]);

        // Return sample response data
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
    }

    public function saveComment($comment, $imageId)
    {

        $this->images = collect($this->images)->map(function ($image) use ($imageId, $comment) {
            if ($image['id'] === $imageId) {
                $image['comments'][] = $comment;
            }

            return $image;
        })->toArray();

        // Log the comment data for debugging
        Log::info('Comment saved via saveComment method', [
            'comment' => $comment,
            'image_id' => $imageId,
            'updated_images' => $this->images,
        ]);

        // Return sample response data
        return [
            'success' => true,
            'comment' => $comment,
            'message' => 'Comment saved to image successfully',
        ];
    }

    public function render()
    {
        return view('livewire.previewtest');
    }
}
