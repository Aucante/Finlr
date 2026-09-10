<?php

namespace Tests\Feature;

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/settings');

        $response->assertOk();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get('/settings')->assertRedirect(route('login'));

        $this->patch('/settings', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assertRedirect(route('login'));
    }

    public function test_settings_routes_are_closed_to_users_with_an_unverified_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/settings')
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)->patch('/settings', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assertRedirect(route('verification.notice'));

        $this->actingAs($user)->delete('/settings', [
            'password' => 'password',
        ])->assertRedirect(route('verification.notice'));

        $this->assertNotNull($user->fresh());
    }

    public function test_two_factor_settings_routes_are_closed_to_users_with_an_unverified_email(): void
    {
        // CONCEPTION.md, section 5, "Middleware verified sur les routes
        // 2FA" — activating 2FA on an unverified mailbox would let a user
        // lock themselves out of their own account.
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post('/settings/two-factor')
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)->post('/settings/two-factor/confirm', ['code' => '000000'])
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)->delete('/settings/two-factor', ['password' => 'password'])
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)->delete('/settings/two-factor/trusted-devices')
            ->assertRedirect(route('verification.notice'));

        $this->assertNull($user->fresh()->two_factor_enabled_at);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/settings', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_old_profile_url_no_longer_exists(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertNotFound();
        $this->actingAs($user)->patch('/profile')->assertNotFound();
        $this->actingAs($user)->delete('/profile')->assertNotFound();
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings')
            ->delete('/settings', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/settings');

        $this->assertNotNull($user->fresh());
    }
}
