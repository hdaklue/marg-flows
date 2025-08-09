<?php

declare(strict_types=1);

namespace App\Services\Document\Contratcs;

use WendellAdriel\ValidatedDTO\Contracts\BaseDTO;

/**
 * For integrity and typsafing.
 * Inhereted from  @see BaseDTO.
 */
interface BlockConfigContract
{
    public function toArray(): array;

    public function toJson($options = 0): string;

    public function toPrettyJson(): string;
}
