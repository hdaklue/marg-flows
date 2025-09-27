<?php

declare(strict_types=1);

namespace App\Services\Document\Actions;

use App\Contracts\Document\Documentable;
use App\DTOs\Document\CreateDocumentDto;
use App\Events\Document\DocumentCreated;
use App\Facades\DocumentManager;
use App\Models\Document;
use App\Models\User;
use App\Services\Document\Contracts\DocumentTemplateContract;
use Hdaklue\Porter\Contracts\AssignableEntity;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class CreateDocument
{
    use AsAction;

    /**
     * @throws Throwable
     */
    public function handle(
        string $name,
        AssignableEntity|User $creator,
        Documentable $targetDocumentable,
        DocumentTemplateContract $template,
    ): Document {
        try {
            $documentDto = CreateDocumentDto::fromTemplate($name, $template);

            $createdDocument = DocumentManager::create($documentDto, $targetDocumentable, $creator);

            DocumentCreated::dispatch($createdDocument, $targetDocumentable, $creator);

            return $createdDocument;
        } catch (Throwable $e) {
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
