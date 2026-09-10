<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Actions\ResolveTrustedDeviceAction;
use App\Modules\Auth\Models\TwoFactorTrustedDevice;
use App\Modules\Auth\Notifications\TwoFactorCodeNotification;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Dedicated Feature suite for ResolveTrustedDeviceAction and
 * IssueTrustedDeviceCookieAction (CONCEPTION.md, "Lot C"). Exercised
 * through the real /login and /two-factor-challenge routes rather than by
 * calling either Action directly, since what actually matters here is
 * their combined effect on the login flow.
 */
class TrustedDeviceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Creates a `two_factor_trusted_devices` row for the given user and
     * returns the plaintext `selector|validator` pair a browser would
     * carry in the `two_factor_trusted` cookie for it — the same shape
     * IssueTrustedDeviceCookieAction produces, built directly here so each
     * test can control ownership/expiry independently of that action.
     */
    private function trustedDeviceCookieFor(User $user, ?Carbon $expiresAt = null): string
    {
        $selector = Str::random(26);
        $validator = Str::random(40);

        TwoFactorTrustedDevice::query()->create([
            'user_id' => $user->id,
            'selector' => $selector,
            'hashed_validator' => Hash::make($validator),
            'expires_at' => $expiresAt ?? TwoFactorTrustedDevice::newExpiry(),
        ]);

        return $selector.'|'.$validator;
    }

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
     * @see TwoFactorLoginTest::latestCode() — same rationale, duplicated
     * rather than shared: each Feature test file in this module already
     * keeps its own small login/notification helpers.
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

    public function test_a_trusted_device_cookie_for_the_correct_user_skips_the_challenge(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $cookie = $this->trustedDeviceCookieFor($user);

        $response = $this->withCookie(ResolveTrustedDeviceAction::COOKIE_NAME, $cookie)->login($user);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        Notification::assertNothingSent();
    }

    public function test_a_trusted_device_cookie_belonging_to_another_user_does_not_skip_the_challenge(): void
    {
        // Anti-regression test for the flaw corrected in CONCEPTION.md
        // (section 4, point 1 de la relecture): a `selector` is an opaque
        // identifier, not a user identifier — without the `user_id` check
        // in ResolveTrustedDeviceAction, this cookie (belonging to
        // $owner) would wrongly exempt $user from their own 2FA.
        Notification::fake();
        $owner = User::factory()->twoFactorEnabled()->create();
        $cookie = $this->trustedDeviceCookieFor($owner);

        $user = User::factory()->twoFactorEnabled()->create();

        $response = $this->withCookie(ResolveTrustedDeviceAction::COOKIE_NAME, $cookie)->login($user);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.challenge'));
        Notification::assertSentTo($user, TwoFactorCodeNotification::class);
    }

    public function test_an_expired_trusted_device_cookie_does_not_skip_the_challenge(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $cookie = $this->trustedDeviceCookieFor($user, now()->subMinute());

        $response = $this->withCookie(ResolveTrustedDeviceAction::COOKIE_NAME, $cookie)->login($user);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.challenge'));
        Notification::assertSentTo($user, TwoFactorCodeNotification::class);
    }

    public function test_no_trusted_device_cookie_does_not_skip_the_challenge(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();

        $response = $this->login($user);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.challenge'));
        Notification::assertSentTo($user, TwoFactorCodeNotification::class);
    }

    public function test_completing_the_challenge_with_remember_device_issues_a_cookie_that_later_skips_the_challenge(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $this->login($user);
        $code = $this->latestCode($user);

        $verifyResponse = $this->post('/two-factor-challenge', ['code' => $code, 'remember_device' => true]);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('two_factor_trusted_devices', 1);

        $issuedCookie = $verifyResponse->getCookie(ResolveTrustedDeviceAction::COOKIE_NAME);
        $this->assertNotNull($issuedCookie, 'Expected IssueTrustedDeviceCookieAction to queue the cookie on the response.');

        $this->post('/logout');
        $this->assertGuest();

        $secondLogin = $this->withCookie(ResolveTrustedDeviceAction::COOKIE_NAME, $issuedCookie->getValue())->login($user);

        $this->assertAuthenticatedAs($user);
        $secondLogin->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_the_trusted_device_cookie_survives_logout_and_still_skips_the_challenge_afterwards(): void
    {
        // CONCEPTION.md, "Lot C", 5th bullet — the counterpart to the
        // header-inspection guard in AuthenticationTest, exercised here as
        // a full functional round trip through /logout and back into
        // /login with the same cookie.
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $cookie = $this->trustedDeviceCookieFor($user);

        $this->withCookie(ResolveTrustedDeviceAction::COOKIE_NAME, $cookie)->login($user);
        $this->assertAuthenticatedAs($user);

        $logoutResponse = $this->withCookie(ResolveTrustedDeviceAction::COOKIE_NAME, $cookie)->post('/logout');
        $this->assertGuest();

        $clearedCookie = collect($logoutResponse->headers->getCookies())
            ->first(fn ($c) => $c->getName() === ResolveTrustedDeviceAction::COOKIE_NAME);
        $this->assertNull($clearedCookie);

        $response = $this->withCookie(ResolveTrustedDeviceAction::COOKIE_NAME, $cookie)->login($user);

        $this->assertAuthenticatedAs($user);
        Notification::assertNothingSent();
    }
}
