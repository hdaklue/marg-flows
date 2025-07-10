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
            'timezone' => 'Africa/Cairo',
        ]);
             $user1->save();
        $tenant1 = $user1->createdTenants()->create([
            'name' => 'Test Tenant',
        ]);

        $tenant2 = $user1->createdTenants()->create([
            'name' => 'Test Tenant 2',
        ]);

        // $tenant1->members()->attach($user1);
        // $tenant2->members()->attach($user1);

   
        $systemRoles = collect(RoleEnum::cases())->map(function ($case) {
            return [
                'name' => $case->value,
            ];
        })->toArray();
        $tenant1->systemRoles()->createMany($systemRoles);
        $tenant2->systemRoles()->createMany($systemRoles);

        // $user1->assignRole(RoleEnum::ADMIN, $tenant1);

        $roleadmin = $tenant1->systemRoles()->where('name', RoleEnum::ADMIN->value)->firstOrFail();

        $tenant1->addParticipant($user1, $roleadmin);
        $user2 = User::factory()->create([
            'name' => 'Test Member',
            'email' => 'member@example.com',
            'password' => bcrypt('password'),
            'account_type' => AccountType::ADMIN->value,
        ]);

        // $tenant1->members()->attach($user2);

        $roleviewer = $tenant2->systemRoles()->where('name', RoleEnum::VIEWER->value)->firstOrFail();
        // $tenant1->addParticipant($user1, $roleadmin);
        $tenant2->addParticipant($user1, $roleviewer);
        // $user1->assignRole(RoleEnum::VIEWER, $tenant2);

        Flow::factory(20)->create(); // Random realistic distribution

        // Create specific scenarios
        // Predictable mixed data
        Flow::factory(10)->create();

        Flow::inRandomOrder(10)->with('tenant')->get()->each(function (Flow $flow) use ($user1) {

            $role = $flow->tenant->systemRoles()->where('name', RoleEnum::VIEWER->value)->firstOrFail();
            $flow->addParticipant($user1, $role, true);
        });

        // Specific test scenarios
        Flow::factory(5)->overdue()->create();     // Guaranteed overdue
        Flow::factory(3)->edgeCases()->create();   // Edge cases
        Flow::factory(10)->scheduled()->create();  // Future projects
    }
}
