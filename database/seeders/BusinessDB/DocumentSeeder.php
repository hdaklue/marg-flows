<?php

declare(strict_types=1);

namespace Database\Seeders\BusinessDB;

use App\Models\Document;
use App\Models\Flow;
use App\Models\User;
use Hdaklue\MargRbac\Enums\Role\RoleEnum;
use Illuminate\Database\Seeder;

final class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating pages in business database...');

        // Get test user from main database
        $testUser = User::where('email', 'test@example.com')->first();
        if (! $testUser) {
            $this->command->warn(
                'Test user not found. Please run main DatabaseSeeder first.',
            );

            return;
        }

        // Get flows from tenants where the test user is a participant
        $userTenants = $testUser->getAssignedTenants();
        if ($userTenants->isEmpty()) {
            $this->command->warn(
                'Test user has no assigned tenants. Please run main DatabaseSeeder first.',
            );

            return;
        }

        $tenantIds = $userTenants->pluck('id')->toArray();
        $flows = Flow::whereIn('tenant_id', $tenantIds)->limit(10)->get();

        if ($flows->isEmpty()) {
            $this->command->warn(
                'No flows found for test user. Please run main DatabaseSeeder first.',
            );

            return;
        }

        $this->command->info(
            "Found {$flows->count()} flows for page creation...",
        );

        // Create 5 pages for each flow
        foreach ($flows as $flow) {
            for ($i = 1; $i <= 5; $i++) {
                $page = Document::factory()->create([
                    'name' => "Page {$i} for Flow {$flow->id}",
                    'documentable_type' => $flow->getMorphClass(),
                    'documentable_id' => $flow->id,
                    'creator_id' => $testUser->id,
                    'tenant_id' => $flow->tenant_id,
                ]);

                // Assign test user as ADMIN to this page
                $page->addParticipant($testUser, RoleEnum::ADMIN);

                $this->command->info("Created page: {$page->name}");
            }
        }

        // Create some additional standalone pages for testing
        for ($i = 1; $i <= 5; $i++) {
            $randomFlow = $flows->random();
            $page = Document::factory()->create([
                'name' => "Standalone Test Page {$i}",
                'documentable_type' => $randomFlow->getMorphClass(),
                'documentable_id' => $randomFlow->id,
                'creator_id' => $testUser->id,
                'tenant_id' => $randomFlow->tenant_id,
            ]);

            // Assign test user as ADMIN to this page
            $page->addParticipant($testUser, RoleEnum::ADMIN);
        }

        $totalPages = Document::count();
        $this->command->info(
            "✅ Created {$totalPages} pages in business database",
        );
    }
}
