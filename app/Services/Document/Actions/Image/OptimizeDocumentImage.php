<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Image;

use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Image\Image;

final class OptimizeDocumentImage
{
    use AsAction;

    public function handle(string $image)
    {
        Image::load($image)->optimize()->save();
    }

    public function asJob(string $image): void
    {
        logger()->info('inside action', ['image' => $image]);
        $this->handle($image);
    }
}
