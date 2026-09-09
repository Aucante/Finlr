<?php

namespace Tests\Unit\Auth;

use App\Modules\Auth\Models\TwoFactorTrustedDevice;
use Tests\TestCase;

class TwoFactorTrustedDeviceTest extends TestCase
{
    public function test_trust_days_constant_is_thirty(): void
    {
        $this->assertSame(30, TwoFactorTrustedDevice::TRUST_DAYS);
    }

    public function test_new_expiry_is_derived_from_the_trust_days_constant(): void
    {
        $this->travelTo(now());

        $expiry = TwoFactorTrustedDevice::newExpiry();

        $this->assertTrue($expiry->equalTo(now()->addDays(TwoFactorTrustedDevice::TRUST_DAYS)));

        $this->travelBack();
    }
}
