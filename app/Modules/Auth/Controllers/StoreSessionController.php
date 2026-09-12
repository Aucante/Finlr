<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Actions\AuthenticateSessionAction;
use App\Modules\Auth\Enums\AuthenticationStatus;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Shared\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class StoreSessionController extends Controller
{
    public function __invoke(LoginRequest $request, AuthenticateSessionAction $action): RedirectResponse
    {
        $status = $action->handle($request);

        if ($status === AuthenticationStatus::PendingTwoFactor) {
            return redirect()->route('two-factor.challenge');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
