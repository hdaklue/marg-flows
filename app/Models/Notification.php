<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInMainDB;
use Illuminate\Notifications\DatabaseNotification;

final class Notification extends DatabaseNotification
{
    use LivesInMainDB;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifications';
}
