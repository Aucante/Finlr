<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Actions\ResendTwoFactorCodeAction;
use App\Modules\Auth\Actions\ResolvePendingTwoFactorUserAction;
use App\Modules\Shared\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResendTwoFactorCodeController extends Controller
{
    public function __invoke(
        Request $request,
        ResolvePendingTwoFactorUserAction $resolvePendingUser,
        ResendTwoFactorCodeAction $action,
    ): RedirectResponse {
        $user = $resolvePendingUser->handle($request);

        $action->handle($request, $user);

        return back();
    }
}
