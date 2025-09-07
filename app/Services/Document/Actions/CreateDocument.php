<?php

declare(strict_types=1);

namespace App\Services\Document\Actions;

use App\Contracts\Document\Documentable;
use App\DTOs\Document\CreateDocumentDto;
use App\Events\Document\DocumentCreated;
use App\Facades\DocumentManager;
use App\Models\User;
use App\Services\Document\Contracts\DocumentTemplateContract;
use Exception;
use Hdaklue\Porter\Contracts\AssignableEntity;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateDocument
{
    use AsAction;

    public function handle(AssignableEntity|User $creator, Documentable $targetDocumentable, DocumentTemplateContract $template)
    {
        try {
            $documentDto = CreateDocumentDto::fromTemplate($template);

            DocumentManager::create($documentDto, $targetDocumentable, $creator);

            DocumentCreated::dispatch($targetDocumentable, $creator);

        } catch (Exception $e) {
            logger()->error('Error creating new document', [
                'documentable' => $targetDocumentable,
                'creator' => $creator,
                'template' => $template,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
