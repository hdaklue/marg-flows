<?php

namespace App\Services\Document;

use Hdaklue\Polish\BasePolisher;

class DocumentVersionPolisher extends BasePolisher
{
    public static function shortKey(string $value): string
    {
        return substr($value, -6);
    }
}
