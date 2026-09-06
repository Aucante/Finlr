<?php

namespace App\Modules\AnalogySimulator\Requests;

use App\Modules\SimulationEngine\DTOs\AnalogyComparisonInputData;
use App\Modules\SimulationEngine\Enums\AccountType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Same non-invariant bounds as RunMultiEnvelopeSimulationRequest (Étape 2,
 * RAPPORT.md §1.1) — the package validates none of these numeric fields
 * either (docs/API.md §2), the bounds here are this application's own.
 */
class RunAnalogyComparisonRequest extends FormRequest
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
            'accountTypeA' => ['required', Rule::enum(AccountType::class)],
            'accountTypeB' => ['required', Rule::enum(AccountType::class)],
            'labelA' => ['nullable', 'string', 'max:255'],
            'labelB' => ['nullable', 'string', 'max:255'],
            'initialAmount' => ['required', 'numeric', 'min:0'],
            'monthlyContribution' => ['required', 'numeric', 'min:0'],
            'durationYears' => ['required', 'integer', 'min:1', 'max:60'],
            'annualReturnRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'managementFeeRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'inflationRate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Resolves labelA/labelB exactly as AnalogyComparisonInputData's own
     * docblock documents: the user's own scenario label if given, the
     * translated default otherwise — never a hardcoded fallback string
     * baked into the DTO itself.
     */
    public function toData(): AnalogyComparisonInputData
    {
        $labelA = $this->string('labelA')->toString();
        $labelB = $this->string('labelB')->toString();

        return new AnalogyComparisonInputData(
            accountTypeA: AccountType::from($this->string('accountTypeA')->toString()),
            accountTypeB: AccountType::from($this->string('accountTypeB')->toString()),
            initialAmount: $this->float('initialAmount'),
            monthlyContribution: $this->float('monthlyContribution'),
            durationYears: $this->integer('durationYears'),
            annualReturnRate: $this->float('annualReturnRate') / 100,
            terRate: 0.0,
            brokerageFeeRate: 0.0,
            managementFeeRate: $this->float('managementFeeRate') / 100,
            custodyFeeRate: 0.0,
            custodyFeeFixedMonthly: 0.0,
            arbitrageFeeRate: 0.0,
            arbitrageFeeFixed: 0.0,
            inflationRate: $this->float('inflationRate') / 100,
            labelA: $labelA !== '' ? $labelA : __('simulator.analogy.defaultLabelA'),
            labelB: $labelB !== '' ? $labelB : __('simulator.analogy.defaultLabelB'),
        );
    }

    // Not part of AnalogyComparisonInputData: storage metadata for the
    // Scenario record itself, distinct from labelA/labelB (which name the
    // two compared scenarios within the comparison, not the saved scenario
    // as a whole) — same separation as every other simulator's name().
    public function name(): ?string
    {
        return $this->string('name')->toString() ?: null;
    }
}
