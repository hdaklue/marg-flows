<?php

declare(strict_types=1);

namespace App\Services\Document\HTTP\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateVideoUploadSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_size' => 'required|integer|min:1',
            'original_filename' => 'required|string|max:255',
            'max_single_file_size' => 'required|integer|min:1',
            'chunks_total' => 'sometimes|integer|min:2',
        ];
    }

    public function messages(): array
    {
        return [
            'file_size.required' => 'File size is required.',
            'file_size.integer' => 'File size must be a valid number.',
            'file_size.min' => 'File size must be greater than 0.',
            'original_filename.required' => 'Original filename is required.',
            'original_filename.string' => 'Filename must be a valid string.',
            'original_filename.max' => 'Filename cannot exceed 255 characters.',
            'max_single_file_size.required' => 'Max single file size is required.',
            'max_single_file_size.integer' => 'Max single file size must be a valid number.',
            'chunks_total.integer' => 'Chunks total must be a valid number.',
            'chunks_total.min' => 'Chunks total must be at least 2.',
        ];
    }

    public function getFileSize(): int
    {
        return (int) $this->input('file_size');
    }

    public function getOriginalFilename(): string
    {
        return $this->input('original_filename');
    }

    public function getMaxSingleFileSize(): int
    {
        return (int) $this->input('max_single_file_size');
    }

    public function getChunksTotal(): ?int
    {
        return $this->has('chunks_total') ? (int) $this->input('chunks_total') : null;
    }
}
