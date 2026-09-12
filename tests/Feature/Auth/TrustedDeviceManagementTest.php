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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Per-device listing and revocation from /settings, on top of the
 * "forget all devices" flow covered by TwoFactorSettingsTest.
 */
class TrustedDeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Returns the created row together with the plaintext
     * `selector|validator` value a browser would carry for it.
     *
     * @return array{TwoFactorTrustedDevice, string}
     */
    private function trustedDeviceFor(User $user, ?string $label = 'Chrome · Windows', ?Carbon $expiresAt = null): array
    {
        $selector = Str::random(26);
        $validator = Str::random(40);

        $device = TwoFactorTrustedDevice::query()->create([
            'user_id' => $user->id,
            'label' => $label,
            'selector' => $selector,
            'hashed_validator' => Hash::make($validator),
            'expires_at' => $expiresAt ?? TwoFactorTrustedDevice::newExpiry(),
        ]);

        return [$device, $selector.'|'.$validator];
    }

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

    public function test_completing_the_challenge_with_remember_device_stores_a_label_derived_from_the_user_agent(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1')
            ->post('/two-factor-challenge', ['code' => $this->latestCode($user), 'remember_device' => true]);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('two_factor_trusted_devices', [
            'user_id' => $user->id,
            'label' => 'Safari · iPhone',
        ]);
    }

    public function test_an_unrecognised_user_agent_stores_a_null_label_instead_of_failing(): void
    {
        Notification::fake();
        $user = User::factory()->twoFactorEnabled()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->withHeader('User-Agent', 'curl/8.5.0')
            ->post('/two-factor-challenge', ['code' => $this->latestCode($user), 'remember_device' => true]);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('two_factor_trusted_devices', [
            'user_id' => $user->id,
            'label' => null,
        ]);
    }

    public function test_the_settings_page_lists_only_the_users_own_active_devices_without_any_secret(): void
    {
        $user = User::factory()->twoFactorEnabled()->create();
        $otherUser = User::factory()->twoFactorEnabled()->create();

        [$device] = $this->trustedDeviceFor($user, 'Firefox · Linux');
        [$legacyDevice] = $this->trustedDeviceFor($user, null);
        $this->trustedDeviceFor($user, 'Expired · Device', now()->subDay());
        $this->trustedDeviceFor($otherUser, 'Someone else · Device');

        $response = $this->actingAs($user)->get('/settings');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Edit')
            ->has('trustedDevices', 2)
            ->where('trustedDevices', fn ($devices) => collect($devices)->pluck('id')->sort()->values()->all()
                === collect([$device->id, $legacyDevice->id])->sort()->values()->all())
            ->has('trustedDevices.0', fn (Assert $row) => $row
                ->hasAll(['id', 'label', 'createdAt', 'expiresAt', 'isCurrent'])
                ->missing('selector')
                ->missing('hashed_validator')
                ->missing('hashedValidator')
                ->missing('user_id')
            )
        );

        // Belt and braces: no secret may appear anywhere in the serialised
        // page (the Inertia payload is embedded in the HTML).
        foreach (TwoFactorTrustedDevice::query()->get() as $row) {
            $response->assertDontSee($row->selector);
            $response->assertDontSee($row->hashed_validator);
        }
    }

    public function test_the_device_matching_the_current_browsers_cookie_is_flagged_as_current(): void
    {
        $user = User::factory()->twoFactorEnabled()->create();
        [$currentDevice, $cookie] = $this->trustedDeviceFor($user, 'Chrome · Windows');
        [$otherDevice] = $this->trustedDeviceFor($user, 'Safari · iPhone');

        $response = $this->actingAs($user)
            ->withCookie(ResolveTrustedDeviceAction::COOKIE_NAME, $cookie)
            ->get('/settings');

        $response->assertInertia(fn (Assert $page) => $page
            ->where('trustedDevices', function ($devices) use ($currentDevice, $otherDevice): bool {
                $byId = collect($devices)->keyBy('id');

                return $byId[$currentDevice->id]['isCurrent'] === true
                    && $byId[$otherDevice->id]['isCurrent'] === false;
            })
        );
    }

    public function test_a_cookie_with_a_wrong_validator_does_not_flag_any_device_as_current(): void
    {
        $user = User::factory()->twoFactorEnabled()->create();
        [$device] = $this->trustedDeviceFor($user);

        $response = $this->actingAs($user)
            ->withCookie(ResolveTrustedDeviceAction::COOKIE_NAME, $device->selector.'|'.Str::random(40))
            ->get('/settings');

        $response->assertInertia(fn (Assert $page) => $page
            ->where('trustedDevices.0.isCurrent', false)
        );
    }

    public function test_a_user_can_forget_one_of_their_own_devices_without_touching_the_others(): void
    {
        $user = User::factory()->twoFactorEnabled()->create();
        [$forgotten] = $this->trustedDeviceFor($user);
        [$kept] = $this->trustedDeviceFor($user);

        $response = $this->actingAs($user)
            ->from('/settings')
            ->delete("/settings/two-factor/trusted-devices/{$forgotten->id}");

        $response->assertRedirect('/settings');
        $response->assertSessionHas('status', 'two-factor-trusted-device-forgotten');
        $this->assertDatabaseMissing('two_factor_trusted_devices', ['id' => $forgotten->id]);
        $this->assertDatabaseHas('two_factor_trusted_devices', ['id' => $kept->id]);
        $this->assertNotNull($user->fresh()->two_factor_enabled_at);
    }

    public function test_a_user_cannot_forget_a_device_belonging_to_another_account(): void
    {
        $attacker = User::factory()->twoFactorEnabled()->create();
        $victim = User::factory()->twoFactorEnabled()->create();
        [$victimDevice, $victimCookie] = $this->trustedDeviceFor($victim);

        $response = $this->actingAs($attacker)
            ->from('/settings')
            ->delete("/settings/two-factor/trusted-devices/{$victimDevice->id}");

        $response->assertNotFound();
        $response->assertSessionMissing('status');
        $this->assertDatabaseHas('two_factor_trusted_devices', [
            'id' => $victimDevice->id,
            'user_id' => $victim->id,
        ]);

        // The victim's device still genuinely skips their challenge.
        $this->post('/logout');
        $this->withCookie(ResolveTrustedDeviceAction::COOKIE_NAME, $victimCookie)
            ->post('/login', ['email' => $victim->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($victim);
    }

    public function test_forgetting_a_device_that_does_not_exist_returns_not_found(): void
    {
        $user = User::factory()->twoFactorEnabled()->create();

        $this->actingAs($user)
            ->delete('/settings/two-factor/trusted-devices/999999')
            ->assertNotFound();
    }

    public function test_forgetting_the_current_browsers_device_expires_its_cookie(): void
    {
        $user = User::factory()->twoFactorEnabled()->create();
        [$device, $cookie] = $this->trustedDeviceFor($user);

        $response = $this->actingAs($user)
            ->withCookie(ResolveTrustedDeviceAction::COOKIE_NAME, $cookie)
            ->delete("/settings/two-factor/trusted-devices/{$device->id}");

        $clearedCookie = collect($response->headers->getCookies())
            ->first(fn ($c) => $c->getName() === ResolveTrustedDeviceAction::COOKIE_NAME);

        $this->assertNotNull($clearedCookie, 'Expected the two_factor_trusted cookie to be explicitly expired on the response.');
        $this->assertTrue($clearedCookie->getExpiresTime() < time());
    }

    public function test_forgetting_another_device_leaves_the_current_browsers_cookie_alone(): void
    {
        $user = User::factory()->twoFactorEnabled()->create();
        [, $cookie] = $this->trustedDeviceFor($user);
        [$otherDevice] = $this->trustedDeviceFor($user);

        $response = $this->actingAs($user)
            ->withCookie(ResolveTrustedDeviceAction::COOKIE_NAME, $cookie)
            ->delete("/settings/two-factor/trusted-devices/{$otherDevice->id}");

        $clearedCookie = collect($response->headers->getCookies())
            ->first(fn ($c) => $c->getName() === ResolveTrustedDeviceAction::COOKIE_NAME);

        $this->assertNull($clearedCookie);
    }
}
