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
     * for one.
     */
    public function __invoke(Request $request, GenerateAndSendTwoFactorCodeAction $action): RedirectResponse
    {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof.
        abort_if($user === null, 403);

        $action->handle($user);

        return back()->with('status', 'two-factor-confirmation-sent');
    }
}
