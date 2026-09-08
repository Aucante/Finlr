<?php

namespace App\Modules\MultiEnvelopeSimulator\Controllers;

use App\Modules\MultiEnvelopeSimulator\Actions\SaveMultiEnvelopeScenarioAction;
use App\Modules\MultiEnvelopeSimulator\Requests\RunMultiEnvelopeSimulationRequest;
use App\Modules\Shared\Controllers\Controller;
use App\Modules\SimulationEngine\Actions\RunMultiEnvelopeCalculationAction;
use Illuminate\Http\RedirectResponse;

class RunMultiEnvelopeSimulationController extends Controller
{
    public function __invoke(
        RunMultiEnvelopeSimulationRequest $request,
        RunMultiEnvelopeCalculationAction $run,
        SaveMultiEnvelopeScenarioAction $save,
    ): RedirectResponse {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof to
        // allow passing a non-nullable User to SaveMultiEnvelopeScenarioAction::handle().
        abort_if($user === null, 403);

        $input = $request->toData();
        $result = $run->handle($input);
        $scenario = $save->handle($user, $input, $result, $request->name());

        return redirect()->route('scenarios.show', $scenario);
    }
}
