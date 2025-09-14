<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Forms\Components\VideoUploadBlock;
use App\Models\Flow;
use Filament\Forms\Components\RichEditor\RichContentRenderer;

final class ExampleController extends Controller
{
    public function show(Flow $flow)
    {
        // Example of how to render rich content with custom blocks
        $renderedContent = RichContentRenderer::make($flow->brief)
            ->customBlocks([
                VideoUploadBlock::class => [
                    'additionalData' => 'value', // Optional additional data
                ],
            ])
            ->toHtml();

        return view('flows.show', [
            'flow' => $flow,
            'renderedContent' => $renderedContent,
        ]);
    }
}
