<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;

final class TestChunkedUpload extends Component
{
    public ?array $data = [];

    public function mount(): void
    {
        $this->data = [];
    }

    public function submit(): void
    {
        // Here you would typically save the data to your model
        session()->flash('success', 'Files uploaded successfully!');

        // Reset data
        $this->data = [];
    }

    public function render()
    {
        return view('livewire.test-chunked-upload');
    }
}
