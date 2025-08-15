<?php

declare(strict_types=1);

namespace App\Services\Upload\Contracts;

use Illuminate\Http\UploadedFile;

interface SimpleUploadStrategyContract extends UploadStrategyContract
{
    /**
     * Handle simple file upload
     */
    public function upload(UploadedFile $file): string;
}