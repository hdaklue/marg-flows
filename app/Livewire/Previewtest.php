<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

final class Previewtest extends Component
{
    public string $image = 'https://picsum.photos/600/900?random=3';

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

    public function saveComment($comment, $imageId)
    {
        $this->images = collect($this->images)->map(function ($image) use (
            $imageId,
            $comment,
        ) {
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
