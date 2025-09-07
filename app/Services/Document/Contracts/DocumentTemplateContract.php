<?php

declare(strict_types=1);

namespace App\Services\Document\Contracts;

interface DocumentTemplateContract
{
    public static function getName(): string;

    public static function getDescription(): string;

    public function toJson(): string;

    public function toArray(): array;

    public function getConfigJson(): string;

    public function getConfingArray(): array;

    public function getDataArray(): array;

    public function getDataJson(): string;
}
