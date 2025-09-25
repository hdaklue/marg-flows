<?php

declare(strict_types=1);

namespace App\Concerns\Database;

trait LivesInMainDB
{
    /**
     * Get the database connection for the model.
     */
    public function getConnectionName(): ?string
    {
        // Use the default connection (main database)
        return config('database.default');
    }
}
