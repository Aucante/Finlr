<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Models\TwoFactorCode;
use App\Modules\Auth\Models\TwoFactorTrustedDevice;
use App\Modules\Auth\Notifications\TwoFactorCodeNotification;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function login(User $user, array $overrides = []): TestResponse
    {
        return $this->post('/login', array_merge([
            'email' => $user->email,
            'password' => 'password',
        ], $overrides));
    }

    /**
     * The most recently sent code — Notification::assertSentTo's callback
     * runs against every matching notification, not just the first, so a
     * resend's fresh code doesn't get shadowed by the original one.
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

    public function test_login_without_two_factor_is_unaffected(): void
    {
        $user = User::factory()->create();

        $response = $this->login($user);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_with_two_factor_and_no_trusted_device_requires_the_challenge(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();

        $response = $this->login($user);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.challenge'));

        $this->get('/dashboard')->assertRedirect('/login');

        Notification::assertSentTo($user, TwoFactorCodeNotification::class);
    }

    public function test_correct_code_completes_the_login(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $this->login($user);
        $code = $this->latestCode($user);

        $response = $this->post('/two-factor-challenge', ['code' => $code]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseMissing('two_factor_codes', ['user_id' => $user->id]);
    }

    public function test_incorrect_code_is_rejected_and_increments_attempts(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $this->login($user);

        $response = $this->post('/two-factor-challenge', ['code' => '000000']);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
        $this->assertSame(1, TwoFactorCode::query()->where('user_id', $user->id)->value('attempts'));
    }

    public function test_expired_code_is_rejected(): void
    {
        // Isolates TwoFactorCode's own expiry check from the pending
        // session's (they share the same 10-minute window at creation, so
        // time-travelling past it would trip the session gate first, in
        // ResolvePendingTwoFactorUserAction, before ever reaching this
        // check — backdating only the row exercises it directly).
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $this->login($user);
        $code = $this->latestCode($user);

        TwoFactorCode::query()->where('user_id', $user->id)->update(['expires_at' => now()->subMinute()]);

        $response = $this->post('/two-factor-challenge', ['code' => $code]);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
    }

    public function test_the_code_is_invalidated_after_five_failed_attempts(): void
    {
        // Spaced 61 seconds apart to stay clear of the structural
        // `two-factor-verify` limiter (perMinute(1), perHour(5)) added
        // after the pre-commit hook flagged its absence — 5 attempts at
        // 61s intervals lands exactly on, not over, its perHour(5) cap,
        // and each individually respects perMinute(1). What is under test
        // here is the TwoFactorCode.attempts column reaching
        // MAX_ATTEMPTS, an independent counter from the RateLimiter.
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $t0 = now();
        $this->travelTo($t0);
        $this->login($user);

        foreach (range(1, 5) as $attempt) {
            $this->travelTo($t0->copy()->addSeconds($attempt * 61));
            $this->post('/two-factor-challenge', ['code' => '000000'])->assertStatus(302);
        }

        $this->assertDatabaseMissing('two_factor_codes', ['user_id' => $user->id]);

        $this->travelBack();
    }

    public function test_a_second_verify_attempt_within_the_same_minute_is_rejected_by_the_route_level_limiter(): void
    {
        // Distinct from the validation-error behaviour already covered by
        // ensureIsNotRateLimited() elsewhere in this file: this asserts
        // the structural throttle:two-factor-verify middleware itself
        // returns 429 on the route, independently of TwoFactorChallengeRequest.
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $this->login($user);

        $this->post('/two-factor-challenge', ['code' => '000000'])->assertStatus(302);
        $response = $this->post('/two-factor-challenge', ['code' => '000000']);

        $response->assertStatus(429);
    }

    public function test_resending_invalidates_the_previous_code(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $this->login($user);
        $firstCode = $this->latestCode($user);

        $this->post('/two-factor-challenge/resend')->assertStatus(302);

        $response = $this->post('/two-factor-challenge', ['code' => $firstCode]);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
    }

    public function test_a_resend_extends_the_pending_session_expiry(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $t0 = now();
        $this->travelTo($t0);
        $this->login($user);

        $this->travelTo($t0->copy()->addMinutes(9));
        $this->post('/two-factor-challenge/resend')->assertStatus(302);

        // 18 minutes after login: past the original 10-minute window, but
        // within the 10 minutes granted by the resend at minute 9.
        $this->travelTo($t0->copy()->addMinutes(18));
        $code = $this->latestCode($user);

        $response = $this->post('/two-factor-challenge', ['code' => $code]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->travelBack();
    }

    public function test_resend_extension_is_capped_at_thirty_minutes_from_the_original_login(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $t0 = now();
        $this->travelTo($t0);
        $this->login($user);

        // Naively this resend would push expires_at to minute 35, but the
        // absolute cap (30 minutes from the original login) must win.
        $this->travelTo($t0->copy()->addMinutes(25));
        $this->post('/two-factor-challenge/resend')->assertStatus(302);

        $this->travelTo($t0->copy()->addMinutes(31));
        $response = $this->get('/two-factor-challenge');

        $response->assertRedirect('/login');

        $this->travelBack();
    }

    public function test_a_second_resend_within_the_cooldown_window_is_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $this->login($user);

        $this->post('/two-factor-challenge/resend')->assertStatus(302);
        $this->post('/two-factor-challenge/resend')->assertStatus(429);
    }

    public function test_a_sixth_resend_within_the_same_hour_is_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $t0 = now();
        $this->travelTo($t0);
        $this->login($user);

        foreach (range(1, 5) as $i) {
            $this->travelTo($t0->copy()->addMinutes($i * 2));
            $this->post('/two-factor-challenge/resend')->assertStatus(302);
        }

        $this->travelTo($t0->copy()->addMinutes(12));
        $this->post('/two-factor-challenge/resend')->assertStatus(429);

        $this->travelBack();
    }

    public function test_accessing_the_challenge_without_a_pending_login_redirects_to_login(): void
    {
        $this->get('/two-factor-challenge')->assertRedirect('/login');
    }

    public function test_a_stale_pending_session_redirects_to_login(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $t0 = now();
        $this->travelTo($t0);
        $this->login($user);

        $this->travelTo($t0->copy()->addMinutes(11));
        $this->get('/two-factor-challenge')->assertRedirect('/login');

        $this->travelBack();
    }

    public function test_disabling_two_factor_mid_challenge_still_allows_login_to_complete(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $this->login($user);
        $code = $this->latestCode($user);

        $user->two_factor_enabled_at = null;
        $user->save();

        $response = $this->post('/two-factor-challenge', ['code' => $code, 'remember_device' => true]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseMissing('two_factor_trusted_devices', ['user_id' => $user->id]);
    }

    public function test_remember_web_cookie_is_never_set_when_the_challenge_is_completed(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $this->login($user, ['remember' => 'on']);
        $code = $this->latestCode($user);

        $response = $this->post('/two-factor-challenge', ['code' => $code]);

        $this->assertAuthenticatedAs($user);

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));

        $this->assertNull($cookie);
    }

    public function test_remember_web_cookie_is_never_set_when_the_challenge_is_skipped_via_a_trusted_device(): void
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

        $response = $this->withCookie('two_factor_trusted', $selector.'|'.$validator)
            ->login($user, ['remember' => 'on']);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));

        $this->assertNull($cookie);
    }
}
