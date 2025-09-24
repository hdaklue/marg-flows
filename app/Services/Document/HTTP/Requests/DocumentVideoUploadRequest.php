<?php

declare(strict_types=1);

namespace App\Services\Document\HTTP\Requests;

use App\Support\FileTypes;
use Illuminate\Foundation\Http\FormRequest;

final class DocumentVideoUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isChunkedUpload()) {
            return $this->chunkedUploadRules();
        }

        return $this->directUploadRules();
    }

    public function messages(): array
    {
        return [
            'video.required' => 'No file selected. Please choose a video to upload.',
            'video.mimes' => 'Invalid file format. Supported formats: MP4, WebM, OGG',
            'video.max' => 'File is too large. Maximum size allowed is :max KB.',
            'fileName.required' => 'File name is required for chunked uploads.',
            'fileName.regex' => 'File must have a valid video extension (mp4, webm, ogg, ogv).',
            'fileKey.required' => 'File key is required for chunked uploads.',
            'chunk.required' => 'Chunk index is required for chunked uploads.',
            'chunk.min' => 'Chunk index must be 0 or greater.',
            'chunks.required' => 'Total chunks count is required for chunked uploads.',
            'chunks.min' => 'Chunked uploads must have at least 2 chunks.',
        ];
    }

    public function isChunkedUpload(): bool
    {
        return $this->input('chunks', 1) > 1;
    }

    public function getFileKey(): string
    {
        return $this->input('fileKey', uniqid() . '_' . time());
    }

    public function getFileName(): string
    {
        return $this->input('fileName', $this->file('video')->getClientOriginalName());
    }

    public function getChunkIndex(): int
    {
        return (int) $this->input('chunk', 0);
    }

    public function getTotalChunks(): int
    {
        return (int) $this->input('chunks', 1);
    }

    private function directUploadRules(): array
    {
        return [
            'video' => [
                'required',
                'file',
                FileTypes::getStreamVideoForLaravelValidation(),
                'max:' . $this->getMaxFileSizeKB(),
            ],
            'fileKey' => 'sometimes|string|max:255',
            'fileName' => 'sometimes|string|max:255',
            'session_id' => 'sometimes|string|max:255',
        ];
    }

    private function chunkedUploadRules(): array
    {
        return [
            'video' => [
                'required',
                'file',
                'max:' . $this->getMaxChunkSizeKB(), // Max chunk size
            ],
            'fileKey' => 'required|string|max:255',
            'fileName' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!FileTypes::isStreamableVideo($value)) {
                        $fail('File must have a valid video extension (mp4, webm, ogg).');
                    }
                },
            ],
            'chunk' => 'required|integer|min:0',
            'chunks' => 'required|integer|min:2', // Must be chunked if chunks > 1
            'session_id' => 'sometimes|string|max:255',
        ];
    }

    private function getMaxFileSizeKB(): int
    {
        // Default to 500MB in KB - should be overridden by plan-based config
        return config('video-upload.validation.max_file_size', 512000);
    }

    private function getMaxChunkSizeKB(): int
    {
        // Default to 50MB per chunk in KB
        return config('video-upload.validation.max_chunk_size', 51200);
    }
}
