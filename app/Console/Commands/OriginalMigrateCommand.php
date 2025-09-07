<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OriginalMigrateCommand extends Command
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
        $this->info(
            'Running migrations on original database (mysql connection)...',
        );

        try {
            // Run migrations on mysql connection, excluding RBAC migrations
            $exitCode = Artisan::call('migrate', array_filter([
                '--database' => 'mysql',
                '--force' => $this->option('force'),
                '--pretend' => $this->option('pretend'),
            ]));

            if ($exitCode === 0) {
                $this->info(
                    '✅ Original database migrations completed successfully!',
                );

                if ($this->option('seed')) {
                    $this->info('Running seeders on original database...');
                    Artisan::call('db:seed', ['--database' => 'mysql']);
                    $this->info('✅ Original database seeding completed!');
                }
            } else {
                $this->error('❌ Original database migrations failed!');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
