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
        return Inertia::render('Dashboard', [
            'scenarios' => PaginatedData::fromPaginator($listScenarios->handle($request->user()))->toArray(),
        ]);
    }
}
