<?php

namespace App\Modules\FireSimulator\Controllers;

use App\Modules\FireSimulator\Actions\SaveFireScenarioAction;
use App\Modules\FireSimulator\Requests\RunFireProjectionRequest;
use App\Modules\Shared\Controllers\Controller;
use App\Modules\SimulationEngine\Actions\RunFireProjectionAction;
use DomainException;
use Illuminate\Http\RedirectResponse;

class RunFireProjectionController extends Controller
{
    public function __invoke(
        RunFireProjectionRequest $request,
        RunFireProjectionAction $run,
        SaveFireScenarioAction $save,
    ): RedirectResponse {
        $input = $request->toData();

        try {
            $result = $run->handle($input);
        } catch (DomainException $exception) {
            // FireProjectionInput validates its own invariants at
            // construction (docs/API.md §4); RunFireProjectionRequest
            // deliberately doesn't duplicate them (see its own docblock), so
            // an out-of-range value surfaces here as the package's own
            // InvalidFireProjectionInput. Caught via its DomainException
            // supertype rather than importing that class directly, so this
            // module never depends on saucante74/finlr-engine itself
            // (CLAUDE.md).
            return back()->withErrors(['simulation' => __('simulator.fire.form.invalidInput')]);
        }

        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof to
        // allow passing a non-nullable User to SaveFireScenarioAction::handle().
        abort_if($user === null, 403);

        $scenario = $save->handle($user, $input, $result, $request->name());

        return redirect()->route('scenarios.show', $scenario);
    }
}
