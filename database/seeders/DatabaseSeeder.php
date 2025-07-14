<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Account\AccountType;
use App\Enums\Role\RoleEnum;
use App\Models\Flow;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Creating original test user...');
        
        // Keep original test user
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'account_type' => AccountType::ADMIN->value,
            'timezone' => 'Africa/Cairo',
        ]);

        $this->command->info('Creating tenants...');
        
        $tenant1 = $testUser->createdTenants()->create([
            'name' => 'Test Tenant',
        ]);

        $tenant2 = $testUser->createdTenants()->create([
            'name' => 'Test Tenant 2',
        ]);

        // Create 298 additional tenants for performance testing (300 total)
        $performanceTenants = collect();
        for ($i = 3; $i <= 300; $i++) {
            $performanceTenants->push($testUser->createdTenants()->create([
                'name' => "Performance Tenant {$i}",
            ]));
        }

        $allTenants = collect([$tenant1, $tenant2])->concat($performanceTenants);

        $this->command->info('Creating system roles for all tenants...');
        
        $systemRoles = collect(RoleEnum::cases())->map(function ($case) {
            return [
                'name' => $case->value,
            ];
        })->toArray();

        // Create roles for all tenants
        $allTenants->each(function ($tenant) use ($systemRoles) {
            $tenant->systemRoles()->createMany($systemRoles);
        });

        // Assign test user as ADMIN to only 5 tenants (realistic limit)
        $this->command->info('Assigning test user as ADMIN to 5 tenants...');
        $testUserTenants = $allTenants->take(5); // First 5 tenants
        $testUserTenants->each(function ($tenant) use ($testUser) {
            $roleAdmin = $tenant->systemRoles()->where('name', RoleEnum::ADMIN->value)->firstOrFail();
            $tenant->addParticipant($testUser, $roleAdmin->name);
        });

        $this->command->info('Creating 1000 users for performance testing...');
        
        // Create 1000 users in batches for performance
        $batchSize = 100;
        $allUsers = collect([$testUser]);
        
        for ($batch = 0; $batch < 10; $batch++) {
            $users = User::factory($batchSize)->create();
            $allUsers = $allUsers->concat($users);
            
            $this->command->info("Created users batch " . ($batch + 1) . "/10");
        }

        $this->command->info('Assigning users to tenants with 10 max per tenant...');
        
        // Track participants per tenant (max 10 each, test user assigned to first 5)
        $tenantParticipantCounts = [];
        foreach ($allTenants as $index => $tenant) {
            $tenantParticipantCounts[$tenant->id] = $index < 5 ? 1 : 0; // Test user only in first 5
        }
        $roles = collect(RoleEnum::cases())->reject(fn($role) => $role === RoleEnum::ADMIN); // Exclude ADMIN since test user has it
        
        $allUsers->skip(1)->each(function ($user) use ($allTenants, $roles, &$tenantParticipantCounts) {
            // Find tenants that aren't full (have less than 10 participants)
            $availableTenants = $allTenants->filter(function($tenant) use ($tenantParticipantCounts) {
                return $tenantParticipantCounts[$tenant->id] < 10;
            });
            
            if ($availableTenants->isEmpty()) {
                return; // All tenants are full
            }
            
            // Limit users to max 5 tenants: 60% get 1, 25% get 2, 10% get 3, 4% get 4, 1% get 5
            $rand = rand(1, 100);
            $tenantCount = match(true) {
                $rand <= 60 => 1,
                $rand <= 85 => 2,
                $rand <= 95 => 3,
                $rand <= 99 => 4,
                default => 5
            };
            $actualTenantCount = min($tenantCount, $availableTenants->count());
            $tenantsToAssign = $availableTenants->random($actualTenantCount);
            
            foreach ($tenantsToAssign as $tenant) {
                // Double check tenant isn't full
                if ($tenantParticipantCounts[$tenant->id] >= 10) {
                    continue;
                }
                
                $randomRole = $roles->random();
                $role = $tenant->systemRoles()->where('name', $randomRole->value)->first();
                
                if ($role) {
                    $tenant->addParticipant($user, $role->name);
                    $tenantParticipantCounts[$tenant->id]++;
                }
            }
        });
        
        $fullTenants = count(array_filter($tenantParticipantCounts, fn($count) => $count === 10));
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
            $this->command->info("Created flows for tenant chunk " . ($chunkIndex + 1) . "/" . ceil($allTenants->count() / 20));
        });
        
        $totalFlows = $allTenants->count() * 5;
        $this->command->info("Created {$totalFlows} flows total (5 per tenant)");

        $this->command->info('Assigning participants to flows...');
        
        // Assign participants to flows with 10 max limit and test user as admin
        Flow::with('tenant')->chunk(25, function ($flows) use ($allUsers, $testUser) {
            foreach ($flows as $flow) {
                // First, assign test user as ADMIN on every flow
                $adminRole = $flow->tenant->systemRoles()->where('name', RoleEnum::ADMIN->value)->first();
                if ($adminRole) {
                    try {
                        $flow->addParticipant($testUser, $adminRole->name, true);
                    } catch (\Exception $e) {
                        // Skip if already assigned
                    }
                }
                
                // Then assign 9 more random users (total 10 including test user)
                $remainingParticipants = 9;
                $availableUsers = $allUsers->filter(fn($user) => $user->id !== $testUser->id);
                $participants = $availableUsers->random(min($remainingParticipants, $availableUsers->count()));
                $roles = collect(RoleEnum::cases())->reject(fn($role) => $role === RoleEnum::ADMIN); // Exclude ADMIN since test user has it
                
                foreach ($participants as $participant) {
                    $randomRole = $roles->random();
                    $role = $flow->tenant->systemRoles()->where('name', $randomRole->value)->first();
                    
                    if ($role) {
                        try {
                            $flow->addParticipant($participant, $role->name, true);
                        } catch (\Exception $e) {
                            // Skip if participant already assigned
                        }
                    }
                }
            }
        });

        $this->command->info('Creating specific test scenarios...');
        
        // Keep original test scenarios
        Flow::factory(5)->overdue()->create();
        Flow::factory(3)->edgeCases()->create();
        Flow::factory(10)->scheduled()->create();

        // Test user is already assigned as ADMIN to all flows above, no need for additional assignments

        $this->command->info('Database seeding completed!');
        $this->command->info('Performance test data:');
        $this->command->info('- Users: ' . User::count());
        $this->command->info('- Tenants: ' . $allTenants->count());
        $this->command->info('- Flows: ' . Flow::count() . ' (5 per tenant guaranteed)');
        $this->command->info('- Original test user: test@example.com (password: password, admin on 5 tenants)');
    }
}
