<?php

namespace App\Modules\SingleEnvelopeSimulator\Requests;

use App\Modules\SimulationEngine\DTOs\CalculationInputData;
use App\Modules\SimulationEngine\Enums\TaxWrapper;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RunSingleEnvelopeSimulationRequest extends FormRequest
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
            'initialCapital' => ['required', 'numeric', 'min:0'],
            'monthlyContribution' => ['required', 'numeric', 'min:0'],
            'annualRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'years' => ['required', 'integer', 'min:1', 'max:60'],
            'wrapperFee' => ['required', 'numeric', 'min:0', 'max:100'],
            'fundFee' => ['required', 'numeric', 'min:0', 'max:100'],
            'taxRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'inflationRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'inflationEnabled' => ['required', 'boolean'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * The wrapper comes from the resolved route segment, never from the
     * request body: a forged POST carrying its own `wrapper` field cannot
     * desynchronise the URL from the value actually computed and stored.
     */
    public function toData(TaxWrapper $wrapper): CalculationInputData
    {
        return new CalculationInputData(
            initialCapital: $this->float('initialCapital'),
            monthlyContribution: $this->float('monthlyContribution'),
            annualRate: $this->float('annualRate'),
            years: $this->integer('years'),
            wrapperFee: $this->float('wrapperFee'),
            fundFee: $this->float('fundFee'),
            taxRate: $this->float('taxRate'),
            inflationRate: $this->float('inflationRate'),
            inflationEnabled: $this->boolean('inflationEnabled'),
            wrapper: $wrapper,
        );
    }

    // Not part of CalculationInputData: the scenario name is storage
    // metadata, not a financial-engine input, so it travels to
    // SaveSingleEnvelopeScenarioAction separately from toData().
    public function name(): ?string
    {
        return $this->string('name')->toString() ?: null;
    }
}
