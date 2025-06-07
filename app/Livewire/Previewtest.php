<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Previewtest extends Component
{
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

    public function saveComment($comment)
    {

        array_push($this->comments, $comment);
        Log::info(json_encode($comment));

        return response()->json([
            'comment' => $comment,
            'success' => true,
        ]);

    }

    public function render()
    {
        return view('livewire.previewtest', ['comments' => $this->comments]);
    }
}
