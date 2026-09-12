<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Actions\ResolvePendingTwoFactorUserAction;
use App\Modules\Auth\Actions\VerifyTwoFactorCodeAction;
use App\Modules\Auth\Requests\TwoFactorChallengeRequest;
use App\Modules\Shared\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class VerifyTwoFactorCodeController extends Controller
{
    public function __invoke(
        TwoFactorChallengeRequest $request,
        ResolvePendingTwoFactorUserAction $resolvePendingUser,
        VerifyTwoFactorCodeAction $action,
    ): RedirectResponse {
        $user = $resolvePendingUser->handle($request);

        $action->handle($request, $user);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
