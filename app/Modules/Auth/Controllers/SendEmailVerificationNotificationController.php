<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Actions\SendEmailVerificationNotificationAction;
use App\Modules\Shared\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SendEmailVerificationNotificationController extends Controller
{
    public function __invoke(Request $request, SendEmailVerificationNotificationAction $action): RedirectResponse
    {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof to
        // allow passing a non-nullable User to
        // SendEmailVerificationNotificationAction::handle().
        abort_if($user === null, 403);

        if (! $action->handle($user)) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return back()->with('status', 'verification-link-sent');
    }
}
