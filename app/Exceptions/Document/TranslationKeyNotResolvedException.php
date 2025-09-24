<?php

declare(strict_types=1);

namespace App\Exceptions\Document;

use Exception;

final class TranslationKeyNotResolvedException extends Exception
{
    public function __construct(string $key, string $template, null|string $locale = null)
    {
        $message = "Translation key '{$key}' could not be resolved for template '{$template}'";

        if ($locale) {
            $message .= " with locale '{$locale}'";
        }

        parent::__construct($message);
    }
}
