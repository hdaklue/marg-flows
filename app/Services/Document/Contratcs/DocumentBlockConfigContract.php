<?php

declare(strict_types=1);

namespace App\Services\Document\Contratcs;

interface DocumentBlockConfigContract extends BlockConfigContract
{
    public function build(): BlockConfigContract;
}
