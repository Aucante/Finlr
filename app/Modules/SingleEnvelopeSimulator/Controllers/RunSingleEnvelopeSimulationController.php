<?php

namespace App\Modules\SingleEnvelopeSimulator\Controllers;

use App\Modules\Shared\Controllers\Controller;
use App\Modules\SimulationEngine\Actions\RunProjectionCalculationAction;
use App\Modules\SimulationEngine\Enums\TaxWrapper;
use App\Modules\SingleEnvelopeSimulator\Actions\SaveSingleEnvelopeScenarioAction;
use App\Modules\SingleEnvelopeSimulator\Enums\Jurisdiction;
use App\Modules\SingleEnvelopeSimulator\Requests\RunSingleEnvelopeSimulationRequest;
use Illuminate\Http\RedirectResponse;

class RunSingleEnvelopeSimulationController extends Controller
{
    public function __invoke(
        RunSingleEnvelopeSimulationRequest $request,
        Jurisdiction $jurisdiction,
        TaxWrapper $wrapper,
        RunProjectionCalculationAction $run,
        SaveSingleEnvelopeScenarioAction $save,
    ): RedirectResponse {
        abort_unless($jurisdiction->supports($wrapper), 404);

        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof to
        // allow passing a non-nullable User to SaveSingleEnvelopeScenarioAction::handle().
        abort_if($user === null, 403);

        $input = $request->toData($wrapper);
        $result = $run->handle($input);
        $scenario = $save->handle($user, $input, $result, $request->name());

        return redirect()->route('scenarios.show', $scenario);
    }
}
