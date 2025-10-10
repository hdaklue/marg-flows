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
    //    public function getTable(): string
    //    {
    //        if (! isset($this->table)) {
    //            return str_replace(
    //                '\\',
    //                '',
    //                config('database.connections.' . config('database.default') . '.database')
    //                . '.'
    //                . parent::getTable(),
    //            );
    //        }
    //
    //        $defaultDatabase = config('database.connections.'
    //        . config('database.default')
    //        . '.database');
    //
    //        // If table already has database prefix, return as is
    //        if (str_contains($this->table, '.')) {
    //            return $this->table;
    //        }
    //
    //        //        return $defaultDatabase . '.' . $this->table;
    //        return $this->table;
    //    }
    public function getTable(): string
    {
        $defaultConnection = config('database.default');
        $connectionConfig = config("database.connections.{$defaultConnection}");
        $driver = $connectionConfig['driver'] ?? null;

        if ($driver === 'pgsql') {
            $schema = str_replace('-', '_', $connectionConfig['database']); // PostgreSQL-safe name

            return parent::getTable();
        }

        if ($driver === 'mysql') {
            return $connectionConfig['database'] . '.' . parent::getTable();
        }

        return parent::getTable();
    }
}
