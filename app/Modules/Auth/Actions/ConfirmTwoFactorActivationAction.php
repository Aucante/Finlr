<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\TwoFactorCode;
use App\Modules\Auth\Requests\ConfirmTwoFactorActivationRequest;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ConfirmTwoFactorActivationAction
{
    /**
     * `two_factor_enabled_at` is deliberately absent from User's
     * `#[Fillable(...)]` (RAPPORT.md, Lot A) — a security flag must never
     * be mass-assignable — so it is set below via a direct property
     * assignment followed by `save()`, never `update()`.
     *
     * @throws ValidationException
     */
    public function handle(ConfirmTwoFactorActivationRequest $request, User $user): void
    {
        $twoFactorCode = TwoFactorCode::query()->where('user_id', $user->id)->first();

        if (
            $twoFactorCode === null
            || $twoFactorCode->expires_at->isPast()
            || ! Hash::check($request->string('code')->toString(), $twoFactorCode->code_hash)
        ) {
            $this->registerFailedAttempt($twoFactorCode);

            throw ValidationException::withMessages([
                'code' => trans('auth.two_factor.challenge.invalid_code'),
            ]);
        }

        $twoFactorCode->delete();

        $user->two_factor_enabled_at = now();
        $user->save();
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
