<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Models\TwoFactorCode;
use App\Modules\Auth\Models\TwoFactorTrustedDevice;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'password-updated')
            ->assertRedirect('/settings');

        $this->assertTrue(Hash::check('NewPassword1!', $user->refresh()->password));
    }

    public function test_password_must_satisfy_the_strength_policy(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'alllowercase1',
                'password_confirmation' => 'alllowercase1',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/settings');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_password_strength_errors_are_translated_in_english(): void
    {
        app()->setLocale('en');

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'alllowercase1',
                'password_confirmation' => 'alllowercase1',
            ]);

        $response->assertSessionHasErrors([
            'password' => 'The password field must contain at least one uppercase and one lowercase letter.',
        ]);
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect('/settings');
    }

    public function test_updating_the_password_purges_trusted_devices_and_any_pending_two_factor_code(): void
    {
        // Independent from the Lot D "disabling 2FA purges devices" test
        // (CONCEPTION.md, section 4, point 2a de la relecture) — a
        // password change is the reflex gesture after a suspected
        // compromise, so UpdatePasswordAction purges on its own, even with
        // 2FA left enabled.
        $user = User::factory()->twoFactorEnabled()->create();

        TwoFactorTrustedDevice::query()->create([
            'user_id' => $user->id,
            'selector' => Str::random(26),
            'hashed_validator' => Hash::make(Str::random(40)),
            'expires_at' => TwoFactorTrustedDevice::newExpiry(),
        ]);

        TwoFactorCode::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(TwoFactorCode::VALIDITY_MINUTES),
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/settings')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('two_factor_trusted_devices', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('two_factor_codes', ['user_id' => $user->id]);
    }
}
