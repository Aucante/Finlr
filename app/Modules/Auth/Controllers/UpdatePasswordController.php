<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Actions\UpdatePasswordAction;
use App\Modules\Auth\Requests\UpdatePasswordRequest;
use App\Modules\Shared\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class UpdatePasswordController extends Controller
{
    public function __invoke(UpdatePasswordRequest $request, UpdatePasswordAction $action): RedirectResponse
    {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof to
        // allow passing a non-nullable User to UpdatePasswordAction::handle().
        abort_if($user === null, 403);

        $action->handle($user, $request->validated('password'));

        return back()->with('status', 'password-updated');
    }
}
