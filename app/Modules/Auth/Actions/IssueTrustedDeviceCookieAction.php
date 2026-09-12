<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\TwoFactorTrustedDevice;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class IssueTrustedDeviceCookieAction
{
    public function __construct(private readonly DeriveTrustedDeviceLabelAction $deriveLabel) {}

    public function handle(User $user, ?string $userAgent): void
    {
        $selector = Str::random(26);
        $validator = Str::random(40);

        TwoFactorTrustedDevice::query()->create([
            'user_id' => $user->id,
            'label' => $this->deriveLabel->handle($userAgent),
            'selector' => $selector,
            'hashed_validator' => Hash::make($validator),
            'expires_at' => TwoFactorTrustedDevice::newExpiry(),
        ]);

        // Minutes, not days: Cookie::make()'s $minutes parameter — deriving
        // it from the same TRUST_DAYS constant used for the DB row's
        // expires_at is what keeps the cookie and the row from ever
        // drifting apart (see TwoFactorTrustedDevice::newExpiry()).
        Cookie::queue(Cookie::make(
            name: ResolveTrustedDeviceAction::COOKIE_NAME,
            value: $selector.'|'.$validator,
            minutes: TwoFactorTrustedDevice::TRUST_DAYS * 24 * 60,
            httpOnly: true,
        ));
    }
}
