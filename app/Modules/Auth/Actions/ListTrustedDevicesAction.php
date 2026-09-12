<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DTOs\TrustedDeviceData;
use App\Modules\Auth\Models\TwoFactorTrustedDevice;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;

class ListTrustedDevicesAction
{
    public function __construct(private readonly ResolveTrustedDeviceAction $resolveTrustedDevice) {}

    /**
     * Expired rows are left out: they no longer skip the challenge, so
     * listing them would suggest a trust that doesn't exist anymore.
     *
     * @return list<TrustedDeviceData>
     */
    public function handle(Request $request, User $user): array
    {
        $currentDeviceId = $this->resolveTrustedDevice->currentBrowserDevice($request, $user)?->id;

        return TwoFactorTrustedDevice::query()
            ->where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TwoFactorTrustedDevice $device): TrustedDeviceData => TrustedDeviceData::fromModel(
                $device,
                isCurrent: $device->id === $currentDeviceId,
            ))
            ->values()
            ->all();
    }
}
