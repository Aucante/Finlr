<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Exceptions\PendingTwoFactorSessionExpiredException;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ResolvePendingTwoFactorUserAction
{
    /**
     * Re-reads the pending user fresh from the database on every hit to a
     * challenge route (not a value cached from the original login request)
     * — this is what lets a later re-check of `two_factor_enabled_at`
     * (CONCEPTION.md, section 3, point 2b) see a state change made from
     * another device during the challenge.
     *
     * @throws PendingTwoFactorSessionExpiredException
     */
    public function handle(Request $request): User
    {
        $userId = $request->session()->get('two_factor.pending_user_id');
        $expiresAt = $request->session()->get('two_factor.expires_at');

        if ($userId === null || ! $expiresAt instanceof Carbon || $expiresAt->isPast()) {
            $this->forgetPendingState($request);

            throw new PendingTwoFactorSessionExpiredException;
        }

        $user = User::query()->find($userId);

        if ($user === null) {
            $this->forgetPendingState($request);

            throw new PendingTwoFactorSessionExpiredException;
        }

        return $user;
    }

    private function forgetPendingState(Request $request): void
    {
        $request->session()->forget([
            'two_factor.pending_user_id',
            'two_factor.pending_started_at',
            'two_factor.expires_at',
        ]);
    }
}
