<?php

namespace App\Modules\Shared\Controllers;

use App\Modules\Scenarios\Actions\ListUserScenariosAction;
use App\Modules\Shared\DTOs\PaginatedData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowDashboardController extends Controller
{
    public function __invoke(Request $request, ListUserScenariosAction $listScenarios): Response
    {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never null
        // in practice; PHPStan still needs this explicit proof to allow
        // passing a non-nullable User to ListUserScenariosAction::handle().
        abort_if($user === null, 403);

        return Inertia::render('Dashboard', [
            'scenarios' => PaginatedData::fromPaginator($listScenarios->handle($user))->toArray(),
        ]);
    }
}
