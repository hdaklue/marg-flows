<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

final class OriginalMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'original:migrate 
                            {--force : Force the operation to run when in production}
                            {--pretend : Dump the SQL queries that would be run}
                            {--seed : Seed the database after migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations on the original/main database (mysql connection)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running migrations on original database (mysql connection)...');

        try {
            // Get only main migrations (exclude rbac and business-db subdirectories)
            $mainMigrations = $this->getMainMigrationPaths();

            if (empty($mainMigrations)) {
                $this->info('No main migrations found.');

                return 0;
            }

            // Run migrations on mysql connection, excluding RBAC migrations
            $exitCode = Artisan::call('migrate', array_filter([
                '--database' => 'mysql',
                '--path' => $mainMigrations,
                '--force' => $this->option('force'),
                '--pretend' => $this->option('pretend'),
            ]));

            if ($exitCode === 0) {
                $this->info('✅ Original database migrations completed successfully!');

                if ($this->option('seed')) {
                    $this->info('Running seeders on original database...');
                    Artisan::call('db:seed', ['--database' => 'mysql']);
                    $this->info('✅ Original database seeding completed!');
                }
            } else {
                $this->error('❌ Original database migrations failed!');

                return 1;
            }
        } catch (Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * Get migration paths for main database (excluding subdirectories).
     */
    private function getMainMigrationPaths(): array
    {
        $migrationsPath = database_path('migrations');

        if (! is_dir($migrationsPath)) {
            return [];
        }

        // Get all .php files in the main migrations directory
        $files = glob($migrationsPath . '/*.php');

        // Convert absolute paths to relative paths from Laravel root
        return array_map(function ($file) {
            return 'database/migrations/' . basename($file);
        }, $files);
    }
}
