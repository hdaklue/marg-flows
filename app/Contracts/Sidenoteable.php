<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Sidenoteable
{

    public function getKey();
    public function getMorphClass();
    public function sideNotes():MorphMany;
}
