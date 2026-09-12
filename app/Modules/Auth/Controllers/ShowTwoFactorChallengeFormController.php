<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Actions\ResolvePendingTwoFactorUserAction;
use App\Modules\Shared\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowTwoFactorChallengeFormController extends Controller
{
    public function __invoke(Request $request, ResolvePendingTwoFactorUserAction $resolvePendingUser): Response
    {
        $resolvePendingUser->handle($request);

        return Inertia::render('Auth/TwoFactorChallenge');
    }
}
