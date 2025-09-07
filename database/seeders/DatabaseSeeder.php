<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Flow;
use App\Models\User;
use Exception;
use Hdaklue\MargRbac\Enums\Account\AccountType;
use Hdaklue\Porter\Facades\Porter;
use Hdaklue\Porter\RoleFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Creating original test user...');

        // Keep original test user or use existing one
        $testUser = User::where('email', 'test@example.com')->first();

        if (! $testUser) {
            $testUser = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'account_type' => AccountType::ADMIN->value,
            ]);
        } else {
            $this->command->info('Using existing test user...');
        }

        // Create profile for test user
        $this->command->info('Creating profile for test user...');
        try {

            $testUser->profile()->firstOrCreate(
                ['user_id' => $testUser->id],
                [
                    'timezone' => 'Africa/Cairo',
                ],
            );
            $this->command->info('Profile created for test user');
        } catch (Exception $e) {
            $this->command->warn('Could not create profile for test user: ' . $e->getMessage());
            // Create profile without avatar
            $testUser->profile()->firstOrCreate(
                ['user_id' => $testUser->id],
                ['timezone' => 'Africa/Cairo'],
            );
        }

        $this->command->info('Creating tenants...');

        $tenant1 = $testUser->createdTenants()->create([
            'name' => 'Test Tenant',
        ]);

        $tenant2 = $testUser->createdTenants()->create([
            'name' => 'Test Tenant 2',
        ]);

        // Create 3 additional tenants for the test user admin access (5 total)
        $performanceTenants = collect();
        for ($i = 3; $i <= 5; $i++) {
            $performanceTenants->push($testUser->createdTenants()->create([
                'name' => "Test Tenant {$i}",
            ]));
        }

        // Create additional performance tenants (295 more for 300 total)
        for ($i = 6; $i <= 300; $i++) {
            $performanceTenants->push($testUser->createdTenants()->create([
                'name' => "Performance Tenant {$i}",
            ]));
        }

        $allTenants = collect([$tenant1, $tenant2])->concat($performanceTenants);

        // Assign test user as ADMIN to only first 5 tenants using Porter
        $this->command->info('Assigning test user as ADMIN to 5 tenants using Porter...');
        $testUserTenants = $allTenants->take(5); // First 5 tenants
        $adminRole = RoleFactory::admin();

        $testUserTenants->each(function ($tenant) use ($testUser, $adminRole) {
            try {
                Porter::assign($testUser, $tenant, $adminRole);

                $this->command->info("✓ Assigned admin role to tenant: {$tenant->name}");
            } catch (Exception $e) {
                $this->command->warn("Failed to assign admin role to tenant {$tenant->name}: " . $e->getMessage());
            }
        });
        $testUser->switchActiveTenant($testUserTenants->first());
        $this->command->info('Creating 1000 users for performance testing...');

        // Create 1000 users in batches for performance
        $batchSize = 100;
        $allUsers = collect([$testUser]);

        for ($batch = 0; $batch < 10; $batch++) {
            $users = User::factory($batchSize)->create();
            $allUsers = $allUsers->concat($users);

            // Create profiles for each user in this batch
            $users->each(function ($user) {
                try {
                    $user->profile()->firstOrCreate(
                        ['user_id' => $user->id],
                        [
                            'timezone' => fake()->randomElement([
                                'America/New_York', 'Europe/London', 'Asia/Tokyo', 'Australia/Sydney',
                                'America/Los_Angeles', 'Europe/Paris', 'Asia/Dubai', 'Africa/Cairo',
                            ]),
                        ],
                    );
                } catch (Exception $e) {
                    $this->command->warn("Failed to create profile for user {$user->id}: " . $e->getMessage());
                }
            });

            $this->command->info('Created users batch ' . ($batch + 1) . '/10 with profiles');
        }

        $this->command->info('Assigning users to tenants with Porter system...');

        // Get available roles from Porter (excluding admin since test user has it)
        $availableRoles = collect(RoleFactory::getAllWithKeys())
            ->reject(fn ($role, $key) => $key === 'admin')
            ->values()
            ->toArray();

        // Track participants per tenant (max 10 each, test user assigned to first 5)
        $tenantParticipantCounts = [];
        foreach ($allTenants as $index => $tenant) {
            $tenantParticipantCounts[$tenant->id] = $index < 5 ? 1 : 0; // Test user only in first 5
        }

        $allUsers->skip(1)->each(function ($user) use ($allTenants, $availableRoles, &$tenantParticipantCounts) {
            // Find tenants that aren't full (have less than 10 participants)
            $availableTenants = $allTenants->filter(function ($tenant) use ($tenantParticipantCounts) {
                return $tenantParticipantCounts[$tenant->id] < 10;
            });

            if ($availableTenants->isEmpty()) {
                return; // All tenants are full
            }

            // Limit users to max 5 tenants: 60% get 1, 25% get 2, 10% get 3, 4% get 4, 1% get 5
            $rand = rand(1, 100);
            $tenantCount = match (true) {
                $rand <= 60 => 1,
                $rand <= 85 => 2,
                $rand <= 95 => 3,
                $rand <= 99 => 4,
                default => 5,
            };
            $actualTenantCount = min($tenantCount, $availableTenants->count());
            $tenantsToAssign = $availableTenants->random($actualTenantCount);

            foreach ($tenantsToAssign as $tenant) {
                // Double check tenant isn't full
                if ($tenantParticipantCounts[$tenant->id] >= 10) {
                    continue;
                }

                $randomRole = $availableRoles[array_rand($availableRoles)];

                try {
                    Porter::assign($user, $tenant, $randomRole);
                    $tenantParticipantCounts[$tenant->id]++;
                } catch (Exception $e) {
                    $this->command->warn("Failed to assign role to user {$user->id} on tenant {$tenant->id}: " . $e->getMessage());
                }
            }
        });

        $fullTenants = count(array_filter($tenantParticipantCounts, fn ($count) => $count === 10));
        $this->command->info("Tenants with max participants (10): {$fullTenants}/300");

        $this->command->info('Creating 5 flows per tenant...');

        // Create exactly 5 flows for each tenant to ensure distribution
        $allTenants->chunk(20)->each(function ($tenantChunk, $chunkIndex) use ($allTenants) {
            foreach ($tenantChunk as $tenant) {
                // Create 5 flows for this specific tenant
                Flow::factory(5)->create([
                    'tenant_id' => $tenant->id,
                ]);
            }
            $this->command->info('Created flows for tenant chunk ' . ($chunkIndex + 1) . '/' . ceil($allTenants->count() / 20));
        });

        $totalFlows = $allTenants->count() * 5;
        $this->command->info("Created {$totalFlows} flows total (5 per tenant)");

        $this->command->info('Assigning participants to flows using Porter...');

        // Get available roles for flow assignments
        $flowRoles = collect(RoleFactory::getAllWithKeys())
            ->reject(fn ($role, $key) => $key === 'admin')
            ->values()
            ->toArray();

        $adminRole = RoleFactory::admin();

        // Assign participants to flows with 10 max limit and test user as admin
        Flow::with('tenant')->chunk(25, function ($flows) use ($testUser, $adminRole, $flowRoles) {
            foreach ($flows as $flow) {
                try {
                    // First, assign test user as ADMIN on flows where they have tenant access
                    if (Porter::check($testUser, $flow->tenant, $adminRole)) {
                        try {
                            Porter::assign($testUser, $flow, $adminRole);
                        } catch (Exception $e) {
                            // Skip if already assigned
                        }
                    }

                    // Get tenant participants using Porter
                    $tenantParticipants = Porter::getAssignedEntitiesByType($flow->tenant, Relation::getMorphAlias(User::class));
                    $availableUsers = $tenantParticipants->filter(fn ($user) => $user->id !== $testUser->id);

                    // Assign up to 9 more participants (or fewer if tenant has fewer participants)
                    $maxParticipants = Porter::check($testUser, $flow, $adminRole) ? 9 : 10;
                    $participantsToAssign = $availableUsers->random(min($maxParticipants, $availableUsers->count()));

                    foreach ($participantsToAssign as $participant) {
                        $randomRole = $flowRoles[array_rand($flowRoles)];

                        try {
                            Porter::assign($participant, $flow, $randomRole);
                        } catch (Exception $e) {
                            // Skip if participant already assigned
                        }
                    }
                } catch (Exception $e) {
                    $this->command->warn("Failed to process flow {$flow->id}: " . $e->getMessage());
                }
            }
        });

        $this->command->info('Core database seeding completed!');

        $this->command->info('Creating specific test scenarios...');

        // Keep original test scenarios
        // Flow::factory(5)->overdue()->create();
        // Flow::factory(3)->edgeCases()->create();
        // Flow::factory(10)->scheduled()->create();

        // Test user is already assigned as ADMIN to all flows above, no need for additional assignments

        $this->command->info('Database seeding completed!');
        $this->command->info('Performance test data:');
        $this->command->info('- Users: ' . User::count());
        $this->command->info('- Tenants: ' . $allTenants->count());
        $this->command->info('- Flows: ' . Flow::count() . ' (5 per tenant guaranteed)');
        $this->command->info('- Original test user: test@example.com (password: password, admin on 5 tenants)');
    }
}
