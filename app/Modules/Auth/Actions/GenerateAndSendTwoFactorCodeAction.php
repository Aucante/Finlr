<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\TwoFactorCode;
use App\Modules\Auth\Notifications\TwoFactorCodeNotification;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;

class GenerateAndSendTwoFactorCodeAction
{
    public function handle(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Unique constraint on `user_id` (see migration) makes this an
        // upsert: a resend overwrites the previous row wholesale — hash,
        // expiry and attempt count all reset together, so "un seul code
        // valide à la fois" (CONCEPTION.md, section 1) holds without any
        // separate invalidation step.
        TwoFactorCode::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(TwoFactorCode::VALIDITY_MINUTES),
                'attempts' => 0,
            ],
        );

        $user->notify(new TwoFactorCodeNotification($code));
    }
}
