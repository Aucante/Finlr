<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Actions\ConfirmTwoFactorActivationAction;
use App\Modules\Auth\Requests\ConfirmTwoFactorActivationRequest;
use App\Modules\Shared\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ConfirmTwoFactorActivationController extends Controller
{
    public function __invoke(ConfirmTwoFactorActivationRequest $request, ConfirmTwoFactorActivationAction $action): RedirectResponse
    {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof.
        abort_if($user === null, 403);

        $action->handle($request, $user);

        return back()->with('status', 'two-factor-enabled');
    }
}
