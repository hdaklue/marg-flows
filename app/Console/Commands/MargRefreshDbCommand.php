<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MargRefreshDbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marg:refresh-db 
                            {--force : Force the operation to run when in production}
                            {--seed : Seed the databases after migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh all databases: RBAC, Business, and Original databases';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // SAFETY CHECK: Only allow in local environment
        if (!app()->environment('local', 'testing')) {
            $this->error('❌ This command is only allowed in local/testing environments for safety!');
            $this->warn('Current environment: ' . app()->environment());
            return 1;
        }

        $this->info('🚀 Starting complete database refresh...');
        $this->newLine();

        try {
            // Step 1: Refresh RBAC database
            $this->info('Step 1: Refreshing RBAC database...');
            $exitCode = Artisan::call('rbac:fresh-migrate', array_filter([
                '--force' => $this->option('force'),
            ]));
            
            if ($exitCode !== 0) {
                $this->error('❌ RBAC database refresh failed!');
                return 1;
            }
            $this->info('✅ RBAC database refreshed successfully!');
            $this->newLine();

            // Step 2: Refresh Business database (business_db connection) - if it exists
            if (config('database.connections.business_db')) {
                $this->info('Step 2: Refreshing Business database...');
                
                $exitCode = Artisan::call('migrate:fresh', [
                    '--database' => 'business_db',
                    '--path' => 'database/migrations/business-db',
                    '--force' => $this->option('force'),
                ]);
                
                if ($exitCode !== 0) {
                    $this->error('❌ Business database refresh failed!');
                    return 1;
                }
                
                $this->info('✅ Business database refreshed successfully!');
                $this->newLine();
            } else {
                $this->info('Step 2: Business database connection not configured, skipping...');
                $this->newLine();
            }

            // Step 3: Refresh Original database (mysql connection) with app-specific migrations only
            $this->info('Step 3: Refreshing Original database...');
            
            $exitCode = Artisan::call('migrate:fresh', [
                '--database' => 'mysql',
                '--path' => 'database/migrations',
                '--force' => $this->option('force'),
            ]);
            
            if ($exitCode !== 0) {
                $this->error('❌ Original database refresh failed!');
                return 1;
            }
            
            $this->info('✅ Original database refreshed successfully!');
            $this->newLine();

            // Step 4: Seed databases if requested
            if ($this->option('seed')) {
                $this->info('Step 4: Seeding databases...');
                
                // Seed the main business database
                Artisan::call('db:seed', ['--database' => 'mysql']);
                $this->info('✅ Database seeding completed!');
            }

            $this->newLine();
            $this->info('🎉 All databases refreshed successfully!');
            $this->info('Architecture:');
            $this->info('  - RBAC Database (margrbac): Users, Tenants, Roles, Permissions');
            $this->info('  - Business Database (business_db): Business-specific data');
            $this->info('  - Original Database (mysql): Main application data, Flows, Profiles');

        } catch (\Exception $e) {
            $this->error('❌ Database refresh failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
