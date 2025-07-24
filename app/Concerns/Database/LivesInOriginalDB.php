<?php

declare(strict_types=1);

namespace App\Concerns\Database;

trait LivesInOriginalDB
{
    /**
     * Get the database connection for the model.
     */
    public function getConnectionName(): ?string
    {
        return config('database.default');
    }
    
    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        if (!isset($this->table)) {
            return str_replace(
                '\\', '', 
                config('database.connections.' . config('database.default') . '.database') . '.' . parent::getTable()
            );
        }

        $defaultDatabase = config('database.connections.' . config('database.default') . '.database');
        
        // If table already has database prefix, return as is
        if (str_contains($this->table, '.')) {
            return $this->table;
        }
        
        return $defaultDatabase . '.' . $this->table;
    }
}