<?php

declare(strict_types=1);

namespace App\Concerns\Database;

trait LivesInBusinessDB
{
    /**
     * Get the database connection for the model.
     */
    public function getConnectionName(): null|string
    {
        return 'business_db';
    }

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        $businessDatabase = config('database.connections.business_db.database');

        if (!isset($this->table)) {
            return $businessDatabase . '.' . parent::getTable();
        }

        // If table already has database prefix, return as is
        if (str_contains($this->table, '.')) {
            return $this->table;
        }

        return $businessDatabase . '.' . $this->table;
    }
}
