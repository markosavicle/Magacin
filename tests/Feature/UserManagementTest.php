<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admins_cannot_list_users(): void
    {
        $user = User::factory()->create(['approved' => true]);

        $this->actingAs($user)->get('/users')->assertForbidden();
    }

    public function test_admins_can_list_users(): void
    {
        $admin = User::factory()->create(['approved' => true, 'is_admin' => true]);
        $other = User::factory()->create(['approved' => true]);

        $this->actingAs($admin)
            ->get('/users?sort_by=email&sort_direction=desc')
            ->assertOk()
            ->assertSee($other->email);
    }

    public function test_unknown_sort_columns_fall_back_to_name(): void
    {
        $admin = User::factory()->create(['approved' => true, 'is_admin' => true]);

        $this->actingAs($admin)->get('/users?sort_by=password&sort_direction=sideways')->assertOk();
    }
}
