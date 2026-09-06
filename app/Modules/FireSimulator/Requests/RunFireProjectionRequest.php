<?php

namespace App\Modules\FireSimulator\Requests;

use App\Modules\SimulationEngine\DTOs\FireProjectionInputData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Unlike RunAnalogyComparisonRequest/RunMultiEnvelopeSimulationRequest,
 * FireProjectionInput validates its own invariants at construction
 * (currentAge >= 0, currentCapital >= 0, monthlyContribution >= 0,
 * desiredAnnualIncome > 0, withdrawalRate > 0, annualReturnRate >= -1.0 —
 * docs/API.md §4). Duplicating those bounds here would create a second
 * place that can silently drift from the package's own rules, so this
 * FormRequest only checks that each field is present and of the right
 * type — an out-of-range value surfaces as the package's own
 * InvalidFireProjectionInput, caught in RunFireProjectionController.
 */
class RunFireProjectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'currentAge' => ['required', 'integer'],
            'currentCapital' => ['required', 'numeric'],
            'monthlyContribution' => ['required', 'numeric'],
            'annualReturnRate' => ['required', 'numeric'],
            'desiredAnnualIncome' => ['required', 'numeric'],
            'withdrawalRate' => ['required', 'numeric'],
        ];
    }

    /**
     * `annualReturnRate` is submitted as a percentage (form-facing
     * convention shared with every other simulator) and converted to the
     * fraction FireProjectionInput expects. `withdrawalRate` is the one
     * exception in this whole application: the package itself expects a
     * percentage (4.0 for 4%), not a fraction — see FireProjectionInputData's
     * own docblock — so it is passed through unconverted.
     */
    public function toData(): FireProjectionInputData
    {
        return new FireProjectionInputData(
            currentAge: $this->integer('currentAge'),
            currentCapital: $this->float('currentCapital'),
            monthlyContribution: $this->float('monthlyContribution'),
            annualReturnRate: $this->float('annualReturnRate') / 100,
            desiredAnnualIncome: $this->float('desiredAnnualIncome'),
            withdrawalRate: $this->float('withdrawalRate'),
        );
    }

    // Not part of FireProjectionInputData: storage metadata for the
    // Scenario record itself, same separation as every other simulator's
    // name().
    public function name(): ?string
    {
        return $this->string('name')->toString() ?: null;
    }
}
