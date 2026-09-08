<?php

namespace App\Modules\User\Controllers;

use App\Modules\Shared\Controllers\Controller;
use App\Modules\User\Actions\DeleteAccountAction;
use App\Modules\User\Requests\DeleteAccountRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class DeleteAccountController extends Controller
{
    public function __invoke(DeleteAccountRequest $request, DeleteAccountAction $action): RedirectResponse
    {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof to
        // allow passing a non-nullable User to DeleteAccountAction::handle().
        abort_if($user === null, 403);

        $action->handle($request, $user);

        return Redirect::to('/');
    }
}
