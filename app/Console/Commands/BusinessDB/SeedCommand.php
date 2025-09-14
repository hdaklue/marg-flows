<?php

declare(strict_types=1);

namespace App\Console\Commands\BusinessDB;

use Database\Seeders\BusinessDB\DocumentSeeder;
use Database\Seeders\FeedbackSeeder;
use Illuminate\Console\Command;

final class SeedCommand extends Command
{
    protected $signature = 'business-db:seed
                            {--class= : The class name of the seeder}
                            {--force : Force the operation to run when in production}';

    protected $description = 'Run database seeders for the business database';

    public function handle(): int
    {
        $this->info('Seeding Business Database...');

        $class = $this->option('class');

        if ($class) {
            // Run specific seeder
            $this->runSeeder($class);
        } else {
            // Run all business database seeders in order
            $this->runSeeder(DocumentSeeder::class);
            $this->runSeeder(FeedbackSeeder::class);
        }

        $this->info('✅ Business database seeding completed!');

        return 0;
    }

    private function runSeeder(string $class): void
    {
        $this->info("Running {$class}...");

        $seeder = new $class;
        $seeder->setCommand($this);
        $seeder->run();
    }
}
