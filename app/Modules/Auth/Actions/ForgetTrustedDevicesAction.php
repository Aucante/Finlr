<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\TwoFactorTrustedDevice;
use App\Modules\User\Models\User;

class ForgetTrustedDevicesAction
{
    public function handle(User $user): void
    {
        TwoFactorTrustedDevice::query()->where('user_id', $user->id)->delete();
    }
}
