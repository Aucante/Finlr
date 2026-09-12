<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\TwoFactorCode;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordAction
{
    public function __construct(private readonly ForgetTrustedDevicesAction $forgetTrustedDevices) {}

    /**
     * A password change is the reflex gesture after a suspected
     * compromise, so it also purges every trusted-device row and any
     * pending 2FA code for this user (CONCEPTION.md, section 4, point 2a
     * de la relecture) — leaving either alive here would be inconsistent
     * with the security intent of the change itself.
     */
    public function handle(User $user, string $password): void
    {
        $user->update([
            'password' => Hash::make($password),
        ]);

        $this->forgetTrustedDevices->handle($user);

        TwoFactorCode::query()->where('user_id', $user->id)->delete();
    }
}
