<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\TwoFactorCode;
use App\Modules\Auth\Requests\TwoFactorChallengeRequest;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class VerifyTwoFactorCodeAction
{
    public function __construct(private readonly IssueTrustedDeviceCookieAction $issueTrustedDeviceCookie) {}

    /**
     * @throws ValidationException
     */
    public function handle(TwoFactorChallengeRequest $request, User $user): void
    {
        $request->ensureIsNotRateLimited();

        $twoFactorCode = TwoFactorCode::query()->where('user_id', $user->id)->first();

        if (
            $twoFactorCode === null
            || $twoFactorCode->expires_at->isPast()
            || ! Hash::check($request->string('code')->toString(), $twoFactorCode->code_hash)
        ) {
            RateLimiter::hit($request->throttleKey());
            $this->registerFailedAttempt($twoFactorCode);

            throw ValidationException::withMessages([
                'code' => trans('auth.two_factor.challenge.invalid_code'),
            ]);
        }

        RateLimiter::clear($request->throttleKey());
        $twoFactorCode->delete();

        // $user was re-fetched fresh from the database for this very
        // request by ResolvePendingTwoFactorUserAction, so this already
        // reflects a 2FA disablement made from another device during the
        // challenge (CONCEPTION.md, section 3, point 2b) — no
        // trusted-device row is created for a 2FA that no longer exists.
        if ($request->boolean('remember_device') && $user->two_factor_enabled_at !== null) {
            $this->issueTrustedDeviceCookie->handle($user);
        }

        $request->session()->forget([
            'two_factor.pending_user_id',
            'two_factor.pending_started_at',
            'two_factor.expires_at',
        ]);

        // Forced false regardless of what the original login form's
        // "remember me" checkbox held (CONCEPTION.md, section 3,
        // "Interaction avec remember_web_*") — a remember_web_* cookie on
        // a 2FA account would let a future auto-recall skip this whole
        // challenge.
        Auth::login($user, false);
        $request->session()->regenerate();
    }

    private function registerFailedAttempt(?TwoFactorCode $twoFactorCode): void
    {
        if ($twoFactorCode === null) {
            return;
        }

        if ($twoFactorCode->attempts + 1 >= TwoFactorCode::MAX_ATTEMPTS) {
            $twoFactorCode->delete();

            return;
        }

        $twoFactorCode->increment('attempts');
    }
}
