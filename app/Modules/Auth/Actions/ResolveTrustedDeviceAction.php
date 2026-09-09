<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\TwoFactorTrustedDevice;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResolveTrustedDeviceAction
{
    public const COOKIE_NAME = 'two_factor_trusted';

    /**
     * The `user_id` check below is load-bearing, not incidental: a
     * `selector` is an opaque identifier, not a user identifier, so
     * without it a trusted-device cookie set by user A would silently
     * exempt user B from 2FA on a shared browser (CONCEPTION.md, section
     * 4, point 1 de la relecture — the flaw the plan was corrected for).
     */
    public function isTrusted(Request $request, User $user): bool
    {
        $cookie = $request->cookie(self::COOKIE_NAME);

        if (! is_string($cookie) || ! str_contains($cookie, '|')) {
            return false;
        }

        [$selector, $validator] = explode('|', $cookie, 2);

        $device = TwoFactorTrustedDevice::query()->where('selector', $selector)->first();

        return $device !== null
            && $device->user_id === $user->id
            && Hash::check($validator, $device->hashed_validator)
            && $device->expires_at->isFuture();
    }
}
