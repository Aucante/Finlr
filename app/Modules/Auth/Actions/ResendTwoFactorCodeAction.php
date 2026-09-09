<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Exceptions\PendingTwoFactorSessionExpiredException;
use App\Modules\Auth\Models\TwoFactorCode;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ResendTwoFactorCodeAction
{
    // Not the primary line of defence against abuse — the
    // `two-factor-resend` rate limiter already bounds how many resends can
    // happen in a given window (RateLimitServiceProvider) — but an
    // absolute, readable ceiling: a pending login never outlives this,
    // no matter how many resends it collects (CONCEPTION.md, section 3).
    private const ABSOLUTE_CAP_MINUTES = 30;

    public function __construct(private readonly GenerateAndSendTwoFactorCodeAction $generateAndSend) {}

    /**
     * @throws PendingTwoFactorSessionExpiredException
     */
    public function handle(Request $request, User $user): void
    {
        $this->generateAndSend->handle($user);

        $startedAt = $request->session()->get('two_factor.pending_started_at');

        if (! $startedAt instanceof Carbon) {
            throw new PendingTwoFactorSessionExpiredException;
        }

        $request->session()->put(
            'two_factor.expires_at',
            now()->addMinutes(TwoFactorCode::VALIDITY_MINUTES)->min($startedAt->clone()->addMinutes(self::ABSOLUTE_CAP_MINUTES)),
        );
    }
}
