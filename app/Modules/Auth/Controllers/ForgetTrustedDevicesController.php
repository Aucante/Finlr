<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Actions\ForgetTrustedDevicesAction;
use App\Modules\Auth\Actions\ResolveTrustedDeviceAction;
use App\Modules\Shared\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ForgetTrustedDevicesController extends Controller
{
    /**
     * `Cookie::forget()` lives here, not inside ForgetTrustedDevicesAction:
     * that Action is shared with DisableTwoFactorAction and
     * UpdatePasswordAction (Lot C), neither of which should clear the
     * current browser's cookie as a side effect of purging the database —
     * only this standalone "forget devices" endpoint is documented
     * (CONCEPTION.md, section 5) to also expire the current browser's
     * cookie.
     */
    public function __invoke(Request $request, ForgetTrustedDevicesAction $action): RedirectResponse
    {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof.
        abort_if($user === null, 403);

        $action->handle($user);

        Cookie::queue(Cookie::forget(ResolveTrustedDeviceAction::COOKIE_NAME));

        return back()->with('status', 'two-factor-trusted-devices-forgotten');
    }
}
