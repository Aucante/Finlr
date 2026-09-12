<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Actions\DisableTwoFactorAction;
use App\Modules\Auth\Requests\DisableTwoFactorRequest;
use App\Modules\Shared\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class DisableTwoFactorController extends Controller
{
    public function __invoke(DisableTwoFactorRequest $request, DisableTwoFactorAction $action): RedirectResponse
    {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof.
        abort_if($user === null, 403);

        $action->handle($user);

        return back()->with('status', 'two-factor-disabled');
    }
}
