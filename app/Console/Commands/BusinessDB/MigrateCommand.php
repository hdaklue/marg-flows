<?php

declare(strict_types=1);

namespace App\Console\Commands\BusinessDB;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

final class MigrateCommand extends Command
{
    protected $signature = 'business-db:migrate 
                            {--fresh : Drop all tables and re-run all migrations}
                            {--rollback : Rollback the last database migration}
                            {--reset : Rollback all database migrations}
                            {--refresh : Reset and re-run all migrations}
                            {--seed : Indicates if the seed task should be re-run}
                            {--step= : Number of migrations to execute}
                            {--additional-path=* : Additional path(s) to include beyond business-db folder}
                            {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                            {--force : Force the operation to run when in production}
                            {--pretend : Dump the SQL queries that would be run}';

    protected $description = 'Run database migrations on the business database (business_db connection)';

    public function handle(): int
    {
        $this->info(
            'Running migrations on Business Database (business_db connection)...',
        );

        // Get all business-db migration paths
        $businessDbPaths = $this->getBusinessDbMigrationPaths();

        if (empty($businessDbPaths)) {
            $this->warn(
                'No migrations found in database/migrations/business-db/ folder.',
            );
            $this->info(
                'Create the folder and add your business database migrations there.',
            );

            return 0;
        }

        $this->comment('Found business-db migrations:');
        foreach ($businessDbPaths as $path) {
            $this->line('  - ' . basename($path));
        }
        $this->line('');

        // Build the artisan command arguments
        $command = 'migrate';
        $arguments = [
            '--database' => 'business_db',
            '--path' => $businessDbPaths,
        ];

        // Handle different migration operations
        if ($this->option('fresh')) {
            $command = 'migrate:fresh';
            $this->warn(
                '⚠️  This will DROP ALL TABLES in the business database!',
            );

            if (
                ! $this->option('force')
                && ! $this->confirm('Do you really wish to run this command?')
            ) {
                $this->info('Command cancelled.');

                return 1;
            }
        } elseif ($this->option('rollback')) {
            $command = 'migrate:rollback';
        } elseif ($this->option('reset')) {
            $command = 'migrate:reset';
            $this->warn('⚠️  This will rollback ALL migrations!');

            if (
                ! $this->option('force')
                && ! $this->confirm('Do you really wish to run this command?')
            ) {
                $this->info('Command cancelled.');

                return 1;
            }
        } elseif ($this->option('refresh')) {
            $command = 'migrate:refresh';
            $this->warn('⚠️  This will reset and re-run all migrations!');

            if (
                ! $this->option('force')
                && ! $this->confirm('Do you really wish to run this command?')
            ) {
                $this->info('Command cancelled.');

                return 1;
            }
        }

        // Add optional arguments
        if ($this->option('seed')) {
            $arguments['--seed'] = true;
        }

        if ($this->option('step')) {
            $arguments['--step'] = $this->option('step');
        }

        // Add any additional paths specified by user
        if ($this->option('additional-path')) {
            $additionalPaths = $this->option('additional-path');
            $arguments['--path'] = array_merge(
                $arguments['--path'],
                $additionalPaths,
            );
        }

        if ($this->option('realpath')) {
            $arguments['--realpath'] = true;
        }

        if ($this->option('force')) {
            $arguments['--force'] = true;
        }

        if ($this->option('pretend')) {
            $arguments['--pretend'] = true;
        }

        // Show what we're about to run
        $this->line('');
        $this->comment(
            "Executing: php artisan {$command} "
            . $this->buildArgumentsString($arguments),
        );
        $this->line('');

        // Run the migration command
        $exitCode = Artisan::call($command, $arguments);

        // Display the output
        $this->line(Artisan::output());

        if ($exitCode === 0) {
            $this->info(
                '✅ Business database migrations completed successfully!',
            );
        } else {
            $this->error('❌ Business database migrations failed!');
        }

        return $exitCode;
    }

    private function buildArgumentsString(array $arguments): string
    {
        $parts = [];

        foreach ($arguments as $key => $value) {
            if ($key === '--database') {
                $parts[] = "--database={$value}";
            } elseif (is_bool($value) && $value) {
                $parts[] = $key;
            } elseif (is_array($value)) {
                foreach ($value as $item) {
                    $parts[] = "{$key}={$item}";
                }
            } elseif (! is_bool($value)) {
                $parts[] = "{$key}={$value}";
            }
        }

        return implode(' ', $parts);
    }

    private function getBusinessDbMigrationPaths(): array
    {
        $businessDbPath = database_path('migrations/business-db');

        if (! is_dir($businessDbPath)) {
            return [];
        }

        $files = glob($businessDbPath . '/*.php');

        // Convert absolute paths to relative paths from the Laravel root
        return array_map(function ($file) {
            return 'database/migrations/business-db/' . basename($file);
        }, $files);
    }
}
