<?php

declare(strict_types=1);

namespace App\Services\Flow;

use App\DTOs\Flow\FlowTemplateDto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use SplFileInfo;

class TemplateService
{
    public static function getTemplates(): Collection
    {
        $files = File::files(static::getTemplatesPath(), true);

        return \collect($files)->map(
            callback: fn (SplFileInfo $file): FlowTemplateDto => FlowTemplateDto::fromJson(File::get($file->getRealPath())));

    }

    public static function getTemplate($slug): FlowTemplateDto
    {
        return static::getTemplates()->firstWhere('slug', $slug, true);
    }

    public static function getDefault(): FlowTemplateDto
    {
        return static::getTemplate(\config('flow_statges.default_template'));
    }

    public static function toArray(): array
    {
        return static::getTemplates()->pluck('name', 'slug')->toArray();
    }

    protected static function getTemplatesPath(): string
    {
        return \base_path(\config('flow_statges.folder_path'));
    }
}
