<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_admin_from_config(): void
    {
        config(['app.admin' => ['email' => 'admin@primer.example', 'password' => 'a-long-secret', 'name' => 'Admin']]);

        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', 'admin@primer.example')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertTrue($admin->approved);
        $this->assertTrue(Hash::check('a-long-secret', $admin->password));
    }

    public function test_it_does_nothing_without_credentials(): void
    {
        config(['app.admin' => ['email' => null, 'password' => null, 'name' => 'Admin']]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::count());
    }

    public function test_it_never_resets_an_existing_password(): void
    {
        config(['app.admin' => ['email' => 'admin@primer.example', 'password' => 'from-env', 'name' => 'Admin']]);
        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', 'admin@primer.example')->firstOrFail();
        $admin->update(['password' => 'changed-in-the-app']);

        // The container runs db:seed on every start.
        $this->seed(AdminUserSeeder::class);

        $this->assertTrue(Hash::check('changed-in-the-app', $admin->fresh()->password));
    }
}
