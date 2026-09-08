<?php

namespace App\Modules\User\Controllers;

use App\Modules\Shared\Controllers\Controller;
use App\Modules\User\Actions\UpdateProfileAction;
use App\Modules\User\DTOs\ProfileUpdateData;
use App\Modules\User\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class UpdateProfileController extends Controller
{
    public function __invoke(ProfileUpdateRequest $request, UpdateProfileAction $action): RedirectResponse
    {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof to
        // allow passing a non-nullable User to UpdateProfileAction::handle().
        abort_if($user === null, 403);

        $action->handle($user, ProfileUpdateData::fromRequest($request));

        return Redirect::route('settings.edit');
    }
}
