<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Actions\GenerateAndSendTwoFactorCodeAction;
use App\Modules\Shared\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RequestTwoFactorActivationController extends Controller
{
    /**
     * No dedicated Action beyond GenerateAndSendTwoFactorCodeAction
     * (already used by the login flow, Lot A/B): sending a confirmation
     * code is exactly what that Action does, regardless of who is asking
     * for one. The "already enabled" guard below stays in this
     * controller rather than in that Action: the Action is also called
     * from the login flow precisely when 2FA *is* enabled (to challenge
     * the user), so this precondition is specific to the activation
     * entry point, not to sending a code in general.
     */
    public function __invoke(Request $request, GenerateAndSendTwoFactorCodeAction $action): RedirectResponse
    {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof.
        abort_if($user === null, 403);

        // Server-side guard, not just a frontend button left unrendered:
        // requesting an activation code for an account that already has
        // 2FA enabled is a conflicting request, not a validation error —
        // 409, not a redirect with session errors.
        abort_if($user->two_factor_enabled_at !== null, 409);

        $action->handle($user);

        return back()->with('status', 'two-factor-confirmation-sent');
    }
}
