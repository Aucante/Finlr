<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Actions\ResolveTrustedDeviceAction;
use App\Modules\Auth\Models\TwoFactorCode;
use App\Modules\Auth\Models\TwoFactorTrustedDevice;
use App\Modules\Auth\Notifications\TwoFactorCodeNotification;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class TwoFactorSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @see TwoFactorLoginTest::latestCode() — same rationale, duplicated
     * rather than shared across Feature test files in this module.
     */
    private function latestCode(User $user): string
    {
        $codes = [];

        Notification::assertSentTo(
            $user,
            TwoFactorCodeNotification::class,
            function (TwoFactorCodeNotification $notification) use (&$codes): bool {
                $codes[] = $notification->code;

                return true;
            },
        );

        return (string) end($codes);
    }

    public function test_requesting_activation_sends_a_confirmation_code_without_enabling_two_factor_yet(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/two-factor');

        $response->assertSessionHasNoErrors();
        Notification::assertSentTo($user, TwoFactorCodeNotification::class);
        $this->assertDatabaseHas('two_factor_codes', ['user_id' => $user->id]);
        $this->assertNull($user->fresh()->two_factor_enabled_at);
    }

    public function test_confirming_activation_with_the_correct_code_enables_two_factor(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->actingAs($user)->post('/settings/two-factor');
        $code = $this->latestCode($user);

        $response = $this->actingAs($user)->post('/settings/two-factor/confirm', ['code' => $code]);

        $response->assertSessionHasNoErrors();
        $this->assertNotNull($user->fresh()->two_factor_enabled_at);
        $this->assertDatabaseMissing('two_factor_codes', ['user_id' => $user->id]);
    }

    public function test_confirming_activation_with_an_incorrect_code_does_not_enable_two_factor(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->actingAs($user)->post('/settings/two-factor');

        $response = $this->actingAs($user)->post('/settings/two-factor/confirm', ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->two_factor_enabled_at);
    }

    public function test_a_second_activation_request_within_the_same_minute_is_rejected_by_the_route_level_limiter(): void
    {
        // Prompt point 8 of this lot: same brute-force/spam reasoning as
        // two-factor-resend, applied here to the authenticated,
        // self-scoped equivalent (throttle:two-factor-enable).
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/settings/two-factor')->assertStatus(302);
        $response = $this->actingAs($user)->post('/settings/two-factor');

        $response->assertStatus(429);
    }

    public function test_a_second_activation_confirm_attempt_within_the_same_minute_is_rejected_by_the_route_level_limiter(): void
    {
        // Prompt point 8 of this lot: same brute-force reasoning as
        // two-factor-verify, applied here to the authenticated,
        // self-scoped equivalent (throttle:two-factor-confirm).
        Notification::fake();
        $user = User::factory()->create();
        $this->actingAs($user)->post('/settings/two-factor');

        $this->actingAs($user)->post('/settings/two-factor/confirm', ['code' => '000000'])->assertStatus(302);
        $response = $this->actingAs($user)->post('/settings/two-factor/confirm', ['code' => '000000']);

        $response->assertStatus(429);
    }

    public function test_disabling_two_factor_without_a_password_is_rejected(): void
    {
        $user = User::factory()->twoFactorEnabled()->create();

        $response = $this->actingAs($user)->delete('/settings/two-factor');

        $response->assertSessionHasErrors('password');
        $this->assertNotNull($user->fresh()->two_factor_enabled_at);
    }

    public function test_disabling_two_factor_with_an_incorrect_password_is_rejected(): void
    {
        $user = User::factory()->twoFactorEnabled()->create();

        $response = $this->actingAs($user)->delete('/settings/two-factor', [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertNotNull($user->fresh()->two_factor_enabled_at);
    }

    public function test_disabling_two_factor_with_the_correct_password_disables_it_and_purges_trusted_devices_and_pending_codes(): void
    {
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

        $response = $this->actingAs($user)->delete('/settings/two-factor', [
            'password' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->two_factor_enabled_at);
        $this->assertDatabaseMissing('two_factor_trusted_devices', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('two_factor_codes', ['user_id' => $user->id]);
    }

    public function test_forgetting_trusted_devices_purges_them_without_disabling_two_factor(): void
    {
        $user = User::factory()->twoFactorEnabled()->create();

        TwoFactorTrustedDevice::query()->create([
            'user_id' => $user->id,
            'selector' => Str::random(26),
            'hashed_validator' => Hash::make(Str::random(40)),
            'expires_at' => TwoFactorTrustedDevice::newExpiry(),
        ]);

        $response = $this->actingAs($user)->delete('/settings/two-factor/trusted-devices');

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('two_factor_trusted_devices', ['user_id' => $user->id]);
        $this->assertNotNull($user->fresh()->two_factor_enabled_at);
    }

    public function test_forgetting_trusted_devices_expires_the_current_browsers_cookie(): void
    {
        $user = User::factory()->twoFactorEnabled()->create();
        $selector = Str::random(26);
        $validator = Str::random(40);

        TwoFactorTrustedDevice::query()->create([
            'user_id' => $user->id,
            'selector' => $selector,
            'hashed_validator' => Hash::make($validator),
            'expires_at' => TwoFactorTrustedDevice::newExpiry(),
        ]);

        $response = $this
            ->actingAs($user)
            ->withCookie(ResolveTrustedDeviceAction::COOKIE_NAME, $selector.'|'.$validator)
            ->delete('/settings/two-factor/trusted-devices');

        $clearedCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === ResolveTrustedDeviceAction::COOKIE_NAME);

        $this->assertNotNull($clearedCookie, 'Expected the two_factor_trusted cookie to be explicitly expired on the response.');
        $this->assertTrue($clearedCookie->getExpiresTime() < time());
    }
}
