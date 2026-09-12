<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\TwoFactorTrustedDevice;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ForgetTrustedDeviceAction
{
    public function __construct(private readonly ResolveTrustedDeviceAction $resolveTrustedDevice) {}

    /**
     * The lookup is scoped by `user_id` on purpose — the device id comes
     * straight from the URL, so without it any authenticated user could
     * revoke another account's devices by guessing ids (same class of flaw
     * as the one ResolveTrustedDeviceAction guards against). A device that
     * exists but belongs to someone else is indistinguishable from one that
     * doesn't exist at all (404), so ids of other accounts can't be probed.
     *
     * @throws ModelNotFoundException<TwoFactorTrustedDevice>
     */
    public function handle(Request $request, User $user, int $deviceId): void
    {
        $device = TwoFactorTrustedDevice::query()
            ->where('user_id', $user->id)
            ->whereKey($deviceId)
            ->firstOrFail();

        $isCurrentBrowser = $this->resolveTrustedDevice->currentBrowserDevice($request, $user)?->id === $device->id;

        $device->delete();

        // Only when the forgotten row is this browser's own: the cookie
        // would be inert anyway once its row is gone, but expiring it keeps
        // this endpoint consistent with "forget all devices".
        if ($isCurrentBrowser) {
            Cookie::queue(Cookie::forget(ResolveTrustedDeviceAction::COOKIE_NAME));
        }
    }
}
