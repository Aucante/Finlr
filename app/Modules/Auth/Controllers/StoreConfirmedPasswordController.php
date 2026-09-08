<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Actions\ConfirmPasswordAction;
use App\Modules\Auth\Requests\ConfirmPasswordRequest;
use App\Modules\Shared\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class StoreConfirmedPasswordController extends Controller
{
    public function __invoke(ConfirmPasswordRequest $request, ConfirmPasswordAction $action): RedirectResponse
    {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof to
        // allow passing a non-nullable User to ConfirmPasswordAction::handle().
        abort_if($user === null, 403);

        $action->handle($user, $request->validated('password'));

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
