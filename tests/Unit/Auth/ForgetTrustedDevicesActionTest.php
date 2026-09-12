<?php

namespace Tests\Unit\Auth;

use App\Modules\Auth\Actions\ForgetTrustedDevicesAction;
use App\Modules\Auth\Models\TwoFactorTrustedDevice;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ForgetTrustedDevicesActionTest extends TestCase
{
    use RefreshDatabase;

    private function createTrustedDeviceFor(User $user): void
    {
        TwoFactorTrustedDevice::query()->create([
            'user_id' => $user->id,
            'selector' => Str::random(26),
            'hashed_validator' => Hash::make(Str::random(40)),
            'expires_at' => TwoFactorTrustedDevice::newExpiry(),
        ]);
    }

    public function test_it_deletes_every_trusted_device_row_belonging_to_the_user(): void
    {
        $user = User::factory()->create();
        $this->createTrustedDeviceFor($user);
        $this->createTrustedDeviceFor($user);

        (new ForgetTrustedDevicesAction)->handle($user);

        $this->assertDatabaseCount('two_factor_trusted_devices', 0);
    }

    public function test_it_does_not_touch_another_users_trusted_device_rows(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->createTrustedDeviceFor($user);
        $this->createTrustedDeviceFor($otherUser);

        (new ForgetTrustedDevicesAction)->handle($user);

        $this->assertDatabaseCount('two_factor_trusted_devices', 1);
        $this->assertDatabaseHas('two_factor_trusted_devices', ['user_id' => $otherUser->id]);
    }
}
