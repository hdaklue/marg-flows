<?php

declare(strict_types=1);

namespace App\Services\Document\Facades;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\Document\Contracts\DocumentVersionContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Throwable;

/**
 * @method static DocumentVersion createInitialVersion(Document $document, User $creator)
 * @method static DocumentVersion createNewVersion(Document $document, array|string $content, User $creator)
 * @method static Collection getVersionHistory(Document $document)
 * @method static DocumentVersion|null getVersion(string $versionId)
 * @method static DocumentVersion rollbackToVersion(Document $document, DocumentVersion $version, User $user)
 * @method static array compareVersions(DocumentVersion $oldVersion, DocumentVersion $newVersion)
 * @method static DocumentVersion|null getLatestVersion(Document $document)
 * @method static int pruneOldVersions(Document $document, int $keepCount = 10)
 * @method static bool isCurrentVersionOfItsDocument(DocumentVersion $version)
 * @method static Document applyVersion(DocumentVersion $version) throws Throwable
 */
final class DocumentVersionManager extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return DocumentVersionContract::class;
    }
}
