<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\TwoFactorCode;
use App\Modules\User\Models\User;

class DisableTwoFactorAction
{
    public function __construct(private readonly ForgetTrustedDevicesAction $forgetTrustedDevices) {}

    /**
     * `two_factor_enabled_at` is deliberately absent from User's
     * `#[Fillable(...)]` — set via a direct property assignment followed
     * by `save()`, never `update()` (same rationale as
     * ConfirmTwoFactorActivationAction). Purges every trusted-device row
     * and any pending code (CONCEPTION.md, section 5; prompt point 2 of
     * this lot) so a later re-enable can't be short-circuited by a
     * leftover trusted-device cookie or a stale confirmation code.
     */
    public function handle(User $user): void
    {
        $user->two_factor_enabled_at = null;
        $user->save();

        $this->forgetTrustedDevices->handle($user);

        TwoFactorCode::query()->where('user_id', $user->id)->delete();
    }
}
