<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Actions\ForgetTrustedDeviceAction;
use App\Modules\Shared\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForgetTrustedDeviceController extends Controller
{
    public function __invoke(Request $request, int $trustedDevice, ForgetTrustedDeviceAction $action): RedirectResponse
    {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof.
        abort_if($user === null, 403);

        $action->handle($request, $user, $trustedDevice);

        return back()->with('status', 'two-factor-trusted-device-forgotten');
    }
}
