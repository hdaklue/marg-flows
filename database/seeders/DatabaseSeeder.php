<?php

namespace Database\Seeders;

use App\Models\Flow;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $user->tenants()->create([
            'name' => 'Test Tenant(2)',
        ]);

        $team = $user->tenants()->create([
            'name' => 'Test Team',
        ]);

        $user->update([
            'active_tenant_id' => $team->id,
        ]);

        $team->members()->attach(User::factory()->create([
            'name' => 'Test Member',
            'email' => 'member@example.com',
            'password' => bcrypt('password'),
        ]));

        Flow::factory(20)->create(); // Random realistic distribution

        // Create specific scenarios
        // Predictable mixed data
        Flow::factory(50)->create();

        // Specific test scenarios
        Flow::factory(5)->overdue()->create();     // Guaranteed overdue
        Flow::factory(3)->edgeCases()->create();   // Edge cases
        Flow::factory(10)->scheduled()->create();  // Future projects
    }
}
