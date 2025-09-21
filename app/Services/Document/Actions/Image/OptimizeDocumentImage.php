<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Image;

use Exception;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Image\Image;

final class OptimizeDocumentImage
{
    use AsAction;

    public function handle(string $imagePath, string $disk = 'do_spaces'): void
    {
        $storage = Storage::disk($disk);

        if (! $storage->exists($imagePath)) {
            logger()->warning('Image not found for optimization', ['path' => $imagePath, 'disk' => $disk]);

            return;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'optimize_image_');

        try {
            file_put_contents($tempFile, $storage->get($imagePath));

            Image::load($tempFile)->optimize()->save();

            $storage->put($imagePath, file_get_contents($tempFile));

            logger()->info('Image optimized successfully', ['path' => $imagePath, 'disk' => $disk]);
        } catch (Exception $e) {
            logger()->error('Image optimization failed', [
                'path' => $imagePath,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function asJob(string $imagePath, string $disk = 'do_spaces'): void
    {
        logger()->info('inside action', ['image' => $imagePath, 'disk' => $disk]);
        $this->handle($imagePath, $disk);
    }
}
