<?php

declare(strict_types=1);

namespace App\Services\Document\HTTP\Requests;

use App\Support\FileSize;
use App\Support\FileTypes;
use Illuminate\Foundation\Http\FormRequest;

final class DocumentImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by controller/middleware
    }

    public function rules(): array
    {
        $userPlan = $this->getUserPlan();
        $maxSizeKB = $this->getMaxFileSizeKBForPlan($userPlan);

        return [
            'image' => [
                'required',
                'image',
                FileTypes::getWebImageForLaravelValidation(),
                "max:{$maxSizeKB}",
            ],
        ];
    }

    public function messages(): array
    {
        $userPlan = $this->getUserPlan();
        $maxSizeMB = $this->getMaxFileSizeMBForPlan($userPlan);

        return [
            'image.required' => 'No file selected. Please choose an image to upload.',
            'image.image' => 'File must be a valid image.',
            'image.mimes' => 'Invalid file format. Supported formats: '
                . $this->getSupportedFormatsText(),
            'image.max' => "File is too large. Maximum size allowed is {$maxSizeMB}MB for your {$userPlan} plan.",
        ];
    }

    public function getValidatedFile()
    {
        return $this->file('image');
    }

    public function getMaxAllowedSizeBytes(): int
    {
        $plan = $this->getUserPlan();

        return match ($plan) {
            'simple' => FileSize::fromMB(5),
            'advanced' => FileSize::fromMB(15),
            'ultimate' => FileSize::fromMB(25),
            default => FileSize::fromMB(10),
        };
    }

    private function getUserPlan(): string
    {
        // Try to get plan from the document's context first
        $document = $this->route('document');
        if ($document && method_exists($document, 'getUserPlan')) {
            return $document->getUserPlan();
        }

        // Get user plan from authenticated user
        if (auth()->check() && method_exists(auth()->user(), 'getPlan')) {
            return auth()->user()->getPlan();
        }

        // Fallback: try to get from request context or route parameters
        if ($this->has('user_plan')) {
            return $this->input('user_plan');
        }

        // Default fallback
        return 'simple';
    }

    private function getMaxFileSizeKBForPlan(string $plan): int
    {
        $bytes = match ($plan) {
            'simple' => FileSize::fromMB(5), // 5MB
            'advanced' => FileSize::fromMB(15), // 15MB
            'ultimate' => FileSize::fromMB(25), // 25MB
            default => FileSize::fromMB(10), // 10MB default
        };

        return (int) FileSize::toKB($bytes);
    }

    // Should be Centralized Later
    private function getMaxFileSizeMBForPlan(string $plan): float
    {
        return match ($plan) {
            'simple' => 5.0,
            'advanced' => 15.0,
            'ultimate' => 25.0,
            default => 10.0,
        };
    }

    private function getSupportedFormatsText(): string
    {
        $extensions = FileTypes::getWebImageExtensions();

        return strtoupper(implode(', ', $extensions));
    }
}
