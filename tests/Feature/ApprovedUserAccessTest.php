<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserIsApproved;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApprovedUserAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function protectedPages(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'partners' => ['/partners'],
            'new partner' => ['/partners/create'],
            'invoices' => ['/invoices'],
            'transactions' => ['/transactions'],
            'history' => ['/history'],
            'profile' => ['/profile'],
            'users' => ['/users'],
        ];
    }

    public function test_approved_user_logs_in_and_reaches_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk();
    }

    public function test_unapproved_user_is_refused_at_login_with_a_pending_message(): void
    {
        $user = User::factory()->unapproved()->create();

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => EnsureUserIsApproved::PENDING_MESSAGE]);

        $this->assertGuest();
    }

    public function test_pending_message_is_shown_on_the_login_page(): void
    {
        $user = User::factory()->unapproved()->create();

        $this->followingRedirects()
            ->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertSee('Vaš nalog čeka odobrenje administratora');
    }

    public function test_unapproved_user_with_wrong_password_gets_the_generic_error(): void
    {
        $user = User::factory()->unapproved()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);

        $this->assertGuest();
    }

    #[DataProvider('protectedPages')]
    public function test_approved_user_can_open_protected_pages(string $uri): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get($uri)->assertOk();
    }

    #[DataProvider('protectedPages')]
    public function test_unapproved_session_is_logged_out_on_protected_pages(string $uri): void
    {
        // An admin flag must not bypass approval either.
        $user = User::factory()->unapproved()->create(['is_admin' => true]);

        $this->actingAs($user)
            ->get($uri)
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHasErrors(['email' => EnsureUserIsApproved::PENDING_MESSAGE]);

        $this->assertGuest();
    }

    public function test_unapproved_session_cannot_write_data(): void
    {
        $user = User::factory()->unapproved()->create();

        $this->actingAs($user)
            ->post('/partners', ['name' => 'Ne sme da se upiše'])
            ->assertRedirect(route('login', absolute: false));

        $this->assertGuest();
        $this->assertDatabaseMissing('partners', ['name' => 'Ne sme da se upiše']);
    }

    public function test_unapproved_session_cannot_approve_itself(): void
    {
        $user = User::factory()->unapproved()->create(['is_admin' => true]);

        $this->actingAs($user)->post("/users/{$user->id}/approve");

        $this->assertFalse($user->fresh()->approved);
        $this->assertGuest();
    }

    public function test_revoking_approval_ends_an_existing_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/partners')->assertOk();

        $user->forceFill(['approved' => false])->save();

        $this->get('/partners')->assertRedirect(route('login', absolute: false));
        $this->assertGuest();
    }

    public function test_json_requests_from_unapproved_users_get_403(): void
    {
        $user = User::factory()->unapproved()->create();

        $this->actingAs($user)->getJson('/partners')->assertForbidden();
        $this->assertGuest();
    }

    public function test_new_registrations_are_told_to_wait_for_approval(): void
    {
        $this->post('/register', [
            'name' => 'Novi Korisnik',
            'email' => 'novi@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHas('status', fn (string $status) => str_contains($status, 'čeka odobrenje'));

        $this->assertGuest();
        $this->post('/login', ['email' => 'novi@example.com', 'password' => 'password']);
        $this->assertGuest();
    }
}
