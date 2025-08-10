<?php

namespace App\Livewire;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Forms\Components\ChunkedFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;

/**
 * @property-read Schema $form
 */
class TestChunkedUpload extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Test Chunked Upload')
                    ->description('Upload files using chunked upload for large files')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->required(),
                        
                        ChunkedFileUpload::make('documents')
                            ->label('Documents')
                            ->directory('test-uploads')
                            ->disk('public')
                            ->chunked()
                            ->chunkSize(2 * 1024 * 1024) // 2MB chunks
                            ->maxFiles(5)
                            ->maxSize(100 * 1024 * 1024) // 100MB max
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'text/*'])
                            ->helperText('Upload PDF, image, or text files. Large files will be uploaded in chunks.'),
                        
                        ChunkedFileUpload::make('large_file')
                            ->label('Single Large File')
                            ->directory('large-uploads')
                            ->disk('public')
                            ->chunked()
                            ->chunkSize(5 * 1024 * 1024) // 5MB chunks
                            ->maxFiles(1)
                            ->maxSize(500 * 1024 * 1024) // 500MB max
                            ->helperText('Upload a single large file (up to 500MB).'),
                    ])
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        
        // Here you would typically save the data to your model
        // For now, we'll just show a success message
        
        $this->dispatch('notify', 
            title: 'Success!',
            message: 'Files uploaded successfully!',
            type: 'success'
        );
        
        // Reset form
        $this->form->fill();
    }

    public function render()
    {
        return view('livewire.test-chunked-upload');
    }
}