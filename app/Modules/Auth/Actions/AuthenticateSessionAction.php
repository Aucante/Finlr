<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Enums\AuthenticationStatus;
use App\Modules\Auth\Models\TwoFactorCode;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthenticateSessionAction
{
    public function __construct(
        private readonly ResolveTrustedDeviceAction $resolveTrustedDevice,
        private readonly GenerateAndSendTwoFactorCodeAction $generateAndSendTwoFactorCode,
    ) {}

    /**
     * Auth::validate() rather than Auth::attempt() — checks the
     * credentials without logging in, so a 2FA-enabled account never gets
     * a real session before the code is confirmed (CONCEPTION.md, section
     * 3, "Emplacement de la décision 2FA requise ?"). The decision itself
     * lives here, not in LoginRequest, which keeps strictly its existing
     * validation/throttle role.
     *
     * @throws ValidationException
     */
    public function handle(LoginRequest $request): AuthenticationStatus
    {
        $request->ensureIsNotRateLimited();

        if (! Auth::validate($request->only('email', 'password'))) {
            RateLimiter::hit($request->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($request->throttleKey());

        $user = User::query()->where('email', $request->string('email'))->first();

        // Auth::validate() just confirmed these exact credentials match a
        // user, so this lookup cannot fail in practice; the check exists
        // only to prove non-nullability to PHPStan before using $user
        // below (same rationale as EditSettingsController's abort_if).
        abort_if($user === null, 500);

        if ($user->two_factor_enabled_at !== null && ! $this->resolveTrustedDevice->isTrusted($request, $user)) {
            $request->session()->regenerate();
            $request->session()->put('two_factor.pending_user_id', $user->id);
            $request->session()->put('two_factor.pending_started_at', now());
            $request->session()->put('two_factor.expires_at', now()->addMinutes(TwoFactorCode::VALIDITY_MINUTES));

            $this->generateAndSendTwoFactorCode->handle($user);

            return AuthenticationStatus::PendingTwoFactor;
        }

        // Forced false for any 2FA-enabled account — including the
        // trusted-device-skip case reaching this line directly, not only
        // the post-challenge path in VerifyTwoFactorCodeAction — so a
        // remember_web_* cookie can never exist for such an account
        // (CONCEPTION.md, section 3, "Interaction avec remember_web_*").
        $remember = $user->two_factor_enabled_at === null && $request->boolean('remember');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return AuthenticationStatus::FullyAuthenticated;
    }
}
