<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_root_redirects_authenticated_users_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_root_does_not_let_unapproved_users_reach_the_dashboard(): void
    {
        $user = User::factory()->unapproved()->create();

        $this->actingAs($user)
            ->followingRedirects()
            ->get('/')
            ->assertOk()
            ->assertSee('Vaš nalog čeka odobrenje administratora');

        $this->assertGuest();
    }

    public function test_login_page_links_to_registration(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Nemate nalog?')
            ->assertSee('Registrujte se')
            ->assertSee('href="' . route('register') . '"', false);
    }
}
