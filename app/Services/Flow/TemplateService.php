<?php

declare(strict_types=1);

namespace App\Services\Flow;

use App\DTOs\Flow\FlowTemplateDto;

use function base_path;
use function collect;
use function config;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use SplFileInfo;

final class TemplateService
{
    public static function getTemplates(): Collection
    {
        $files = File::files(self::getTemplatesPath(), true);

        return collect(
            $files,
        )->map(callback: fn (SplFileInfo $file): FlowTemplateDto => FlowTemplateDto::fromJson(File::get($file->getRealPath())));
    }

    public static function getTemplate($slug): FlowTemplateDto
    {
        return self::getTemplates()->firstWhere('slug', $slug, true);
    }

    public static function getDefault(): FlowTemplateDto
    {
        return self::getTemplate(config('flow_stages.default_template'));
    }

    public static function toArray(): array
    {
        return self::getTemplates()->pluck('name', 'slug')->toArray();
    }

    protected static function getTemplatesPath(): string
    {
        return base_path(config('flow_stages.folder_path'));
    }
}
