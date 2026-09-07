<?php

namespace App\Modules\AnalogySimulator\Controllers;

use App\Modules\AnalogySimulator\Actions\SaveAnalogyScenarioAction;
use App\Modules\AnalogySimulator\Requests\RunAnalogyComparisonRequest;
use App\Modules\Shared\Controllers\Controller;
use App\Modules\SimulationEngine\Actions\RunAnalogyComparisonAction;
use Illuminate\Http\RedirectResponse;

class RunAnalogyComparisonController extends Controller
{
    public function __invoke(
        RunAnalogyComparisonRequest $request,
        RunAnalogyComparisonAction $run,
        SaveAnalogyScenarioAction $save,
    ): RedirectResponse {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof to
        // allow passing a non-nullable User to SaveAnalogyScenarioAction::handle().
        abort_if($user === null, 403);

        $input = $request->toData();
        $result = $run->handle($input);
        $scenario = $save->handle($user, $input, $result, $request->name());

        return redirect()->route('scenarios.show', $scenario);
    }
}
