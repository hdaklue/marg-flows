<?php

declare(strict_types=1);

namespace App\Actions\Flow;

use App\DTOs\Document\CreateDocumentDto;
use App\Enums\Role\RoleEnum;
use App\Models\Document;
use App\Models\Flow;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateDocument
{
    use AsAction;

    public function handle(User $creator, Flow $flow, CreateDocumentDto $dto)
    {

        $document = new Document([
            'name' => $dto->name,
            'blocks' => $dto->blocks,
        ]);
        $document->documentable()->associate($flow);

        $document->creator()->associate($creator);
        $document->save();
        $document->addParticipant($creator, RoleEnum::ADMIN);
    }
}
