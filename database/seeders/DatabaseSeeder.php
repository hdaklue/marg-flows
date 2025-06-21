<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Account\AccountType;
use App\Enums\Role\RoleEnum;
use App\Models\Flow;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user1 = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'account_type' => AccountType::ADMIN->value,
        ]);

        $tenant1 = $user1->createdTenants()->create([
            'name' => 'Test Tenant',
        ]);

        $tenant2 = $user1->createdTenants()->create([
            'name' => 'Test Tenant 2',
        ]);

        $tenant1->members()->attach($user1);
        $tenant2->members()->attach($user1);

        $user1->update([
            'active_tenant_id' => $tenant1->id,
        ]);
        $systemRoles = collect(RoleEnum::cases())->map(function ($case) {
            return [
                'name' => $case->value,
                'guard_name' => 'web',
            ];
        })->toArray();
        $tenant1->systemRoles()->createMany($systemRoles);
        $tenant2->systemRoles()->createMany($systemRoles);

        setPermissionsTeamId($tenant1->id);

        $user1->assignRole(RoleEnum::SUPER_ADMIN, $tenant1);

        $user2 = User::factory()->create([
            'name' => 'Test Member',
            'email' => 'member@example.com',
            'password' => bcrypt('password'),
            'account_type' => AccountType::ADMIN->value,
        ]);

        $tenant1->members()->attach($user2);

        $tenant1->assignUserRole($user2, RoleEnum::VIEWER);

        setPermissionsTeamId($tenant2->id);
        $user1->assignRole(RoleEnum::VIEWER, $tenant2);

        Flow::factory(20)->create(); // Random realistic distribution

        // Create specific scenarios
        // Predictable mixed data
        Flow::factory(50)->create();

        Flow::inRandomOrder(10)->get()->each(function (Flow $flow) use ($user1) {
            $flow->addParticipant($user1, RoleEnum::ADMIN->value, true);
        });

        // Specific test scenarios
        Flow::factory(5)->overdue()->create();     // Guaranteed overdue
        Flow::factory(3)->edgeCases()->create();   // Edge cases
        Flow::factory(10)->scheduled()->create();  // Future projects
    }
}
