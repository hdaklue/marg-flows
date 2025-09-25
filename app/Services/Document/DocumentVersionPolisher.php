<?php

declare(strict_types=1);

namespace App\Services\Document;

use Hdaklue\Polish\BasePolisher;

final class DocumentVersionPolisher extends BasePolisher
{
    public static function shortKey(string $value): string
    {
        return substr($value, -6);
    }
}
