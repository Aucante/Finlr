<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Actions\VerifyEmailAction;
use App\Modules\Shared\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyUserEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request, VerifyEmailAction $action): RedirectResponse
    {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof to
        // allow passing a non-nullable User to VerifyEmailAction::handle().
        abort_if($user === null, 403);

        $action->handle($user);

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
