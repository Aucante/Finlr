<?php

namespace App\Modules\Auth\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the named rate limiters guarding the public authentication
 * endpoints. Credential stuffing on the login route is already handled by
 * LoginRequest's own limiter, which is left untouched.
 */
class RateLimitServiceProvider extends ServiceProvider
{
    private const REGISTER_ATTEMPTS_PER_MINUTE = 5;

    private const PASSWORD_RESET_ATTEMPTS_PER_MINUTE = 5;

    private const FORGOT_PASSWORD_ATTEMPTS_PER_MINUTE = 3;

    private const FORGOT_PASSWORD_ATTEMPTS_PER_HOUR_PER_EMAIL = 3;

    private const OAUTH_ATTEMPTS_PER_MINUTE = 10;

    // Tighter than forgot-password's per-hour budget (3) would need to be:
    // this endpoint is only reachable after a correct password already
    // produced a pending 2FA session, unlike forgot-password's public,
    // unauthenticated form (CONCEPTION.md, section 2, point 3 de la
    // relecture, itération 3).
    private const TWO_FACTOR_RESEND_ATTEMPTS_PER_MINUTE = 1;

    private const TWO_FACTOR_RESEND_ATTEMPTS_PER_HOUR = 5;

    private const TWO_FACTOR_RESEND_ATTEMPTS_PER_MINUTE_PER_IP = 5;

    // At least as strict as two-factor-resend, not just "consistent with
    // it": this route is a direct brute-force target on a 6-digit code
    // (1,000,000 combinations), a materially higher-stakes surface than
    // resend's spam/cost concern. TwoFactorChallengeRequest's own
    // ensureIsNotRateLimited() (5 attempts / 60s) stays in place as
    // defense in depth underneath this — the same layering login already
    // has between this structural throttle: middleware and its own
    // applicative limiter.
    private const TWO_FACTOR_VERIFY_ATTEMPTS_PER_MINUTE = 1;

    private const TWO_FACTOR_VERIFY_ATTEMPTS_PER_HOUR = 5;

    private const TWO_FACTOR_VERIFY_ATTEMPTS_PER_MINUTE_PER_IP = 5;

    // Authenticated, self-scoped counterpart to two-factor-resend: sending
    // the /settings activation code is the same "real email, real cost"
    // concern, just without a pending session to key off — the
    // authenticated user's own id is already an unambiguous key.
    private const TWO_FACTOR_ENABLE_ATTEMPTS_PER_MINUTE = 1;

    private const TWO_FACTOR_ENABLE_ATTEMPTS_PER_HOUR = 5;

    private const TWO_FACTOR_ENABLE_ATTEMPTS_PER_MINUTE_PER_IP = 5;

    // Authenticated, self-scoped counterpart to two-factor-verify: same
    // class of endpoint (brute-forcing a 6-digit code), but a single
    // structural layer only — unlike two-factor-verify this is not paired
    // with an applicative FormRequest::ensureIsNotRateLimited(), since
    // guessing this code only ever affects the account the attacker's own
    // session already belongs to (RAPPORT.md, Lot D, spells out the
    // reasoning in full).
    private const TWO_FACTOR_CONFIRM_ATTEMPTS_PER_MINUTE = 1;

    private const TWO_FACTOR_CONFIRM_ATTEMPTS_PER_HOUR = 5;

    private const TWO_FACTOR_CONFIRM_ATTEMPTS_PER_MINUTE_PER_IP = 5;

    public function boot(): void
    {
        RateLimiter::for('register', fn (Request $request): Limit => Limit::perMinute(self::REGISTER_ATTEMPTS_PER_MINUTE)
            ->by($this->ipKey($request)));

        RateLimiter::for('oauth', fn (Request $request): Limit => Limit::perMinute(self::OAUTH_ATTEMPTS_PER_MINUTE)
            ->by($this->ipKey($request)));

        RateLimiter::for('reset-password', fn (Request $request): Limit => Limit::perMinute(self::PASSWORD_RESET_ATTEMPTS_PER_MINUTE)
            ->by($this->ipKey($request)));

        // Two independent budgets: one protects the server from a single host,
        // the other protects a given mailbox from being flooded from many hosts.
        RateLimiter::for('forgot-password', fn (Request $request): array => [
            Limit::perMinute(self::FORGOT_PASSWORD_ATTEMPTS_PER_MINUTE)
                ->by($this->ipKey($request)),
            Limit::perHour(self::FORGOT_PASSWORD_ATTEMPTS_PER_HOUR_PER_EMAIL)
                ->by($this->emailKey($request)),
        ]);

        // Keyed by the pending 2FA user (from session), not by email: at
        // this point in the flow the user is already unambiguously
        // identified server-side, unlike forgot-password which has only a
        // submitted email to go on. The per-minute(1) budget is the
        // cooldown itself, not a separate mechanism.
        RateLimiter::for('two-factor-resend', fn (Request $request): array => [
            Limit::perMinute(self::TWO_FACTOR_RESEND_ATTEMPTS_PER_MINUTE)
                ->by($this->pendingTwoFactorKey($request, 'resend')),
            Limit::perHour(self::TWO_FACTOR_RESEND_ATTEMPTS_PER_HOUR)
                ->by($this->pendingTwoFactorKey($request, 'resend')),
            Limit::perMinute(self::TWO_FACTOR_RESEND_ATTEMPTS_PER_MINUTE_PER_IP)
                ->by($this->ipKey($request)),
        ]);

        // Structural, route-level backstop — independent budget from
        // resend's (distinct key prefix) so a resend never eats into a
        // user's verify attempts or vice versa. Coexists with
        // TwoFactorChallengeRequest::ensureIsNotRateLimited() (5
        // attempts / 60s, unchanged); whichever of the two is hit first
        // blocks the request.
        RateLimiter::for('two-factor-verify', fn (Request $request): array => [
            Limit::perMinute(self::TWO_FACTOR_VERIFY_ATTEMPTS_PER_MINUTE)
                ->by($this->pendingTwoFactorKey($request, 'verify')),
            Limit::perHour(self::TWO_FACTOR_VERIFY_ATTEMPTS_PER_HOUR)
                ->by($this->pendingTwoFactorKey($request, 'verify')),
            Limit::perMinute(self::TWO_FACTOR_VERIFY_ATTEMPTS_PER_MINUTE_PER_IP)
                ->by($this->ipKey($request)),
        ]);

        RateLimiter::for('two-factor-enable', fn (Request $request): array => [
            Limit::perMinute(self::TWO_FACTOR_ENABLE_ATTEMPTS_PER_MINUTE)
                ->by($this->userKey($request)),
            Limit::perHour(self::TWO_FACTOR_ENABLE_ATTEMPTS_PER_HOUR)
                ->by($this->userKey($request)),
            Limit::perMinute(self::TWO_FACTOR_ENABLE_ATTEMPTS_PER_MINUTE_PER_IP)
                ->by($this->ipKey($request)),
        ]);

        RateLimiter::for('two-factor-confirm', fn (Request $request): array => [
            Limit::perMinute(self::TWO_FACTOR_CONFIRM_ATTEMPTS_PER_MINUTE)
                ->by($this->userKey($request)),
            Limit::perHour(self::TWO_FACTOR_CONFIRM_ATTEMPTS_PER_HOUR)
                ->by($this->userKey($request)),
            Limit::perMinute(self::TWO_FACTOR_CONFIRM_ATTEMPTS_PER_MINUTE_PER_IP)
                ->by($this->ipKey($request)),
        ]);
    }

    private function ipKey(Request $request): string
    {
        return 'ip:'.$request->ip();
    }

    private function emailKey(Request $request): string
    {
        return 'email:'.$request->string('email')->lower()->trim()->toString();
    }

    private function pendingTwoFactorKey(Request $request, string $prefix): string
    {
        return '2fa-'.$prefix.':user:'.$request->session()->get('two_factor.pending_user_id');
    }

    // Same style as SingleEnvelopeSimulator's RateLimitServiceProvider::
    // userKey() — these routes sit behind `auth`, so $request->user() is
    // never null in practice.
    private function userKey(Request $request): string
    {
        return 'user:'.$request->user()->id;
    }
}
