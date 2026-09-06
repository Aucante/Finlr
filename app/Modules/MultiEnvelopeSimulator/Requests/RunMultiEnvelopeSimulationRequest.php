<?php

namespace App\Modules\MultiEnvelopeSimulator\Requests;

use App\Modules\SimulationEngine\DTOs\EnvelopeConfigData;
use App\Modules\SimulationEngine\DTOs\MultiEnvelopeCalculationInputData;
use App\Modules\SimulationEngine\Enums\AccountType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * saucante74/finlr-engine documents no validated invariant on EnvelopeConfig
 * or its numeric fields — the constructor accepts a negative amount, a
 * zero/negative duration, or an out-of-range rate without complaint
 * (docs/API.md §2, "⚠ Non vérifié par le paquet"). The bounds below are
 * this application's own, not the package's: `min:0` on every amount,
 * `min:1 max:60` on durationYears and `max:100` on every percentage mirror
 * RunSingleEnvelopeSimulationRequest's existing bounds for the same kind of
 * field. `envelopes` itself needs `min:1` (the engine requires a
 * non-empty-array) and a `max:8` this application chose as a reasonable
 * cap — one envelope per AccountType case — to keep a cascade legible and
 * the request payload bounded, not a limit documented anywhere upstream.
 */
class RunMultiEnvelopeSimulationRequest extends FormRequest
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
            'inflationRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'envelopes' => ['required', 'array', 'min:1', 'max:8'],
            'envelopes.*.accountType' => ['required', Rule::enum(AccountType::class)],
            'envelopes.*.initialAmount' => ['required', 'numeric', 'min:0'],
            'envelopes.*.monthlyContribution' => ['required', 'numeric', 'min:0'],
            'envelopes.*.durationYears' => ['required', 'integer', 'min:1', 'max:60'],
            'envelopes.*.annualReturnRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'envelopes.*.managementFeeRate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function toData(): MultiEnvelopeCalculationInputData
    {
        // Shared across the whole cascade rather than asked per envelope:
        // the engine only ever reads envelopeConfigs[0]->inflationRate, so
        // asking per row would let rows silently disagree with no effect.
        $inflationRate = $this->float('inflationRate') / 100;

        /** @var list<array{accountType: string, initialAmount: mixed, monthlyContribution: mixed, durationYears: mixed, annualReturnRate: mixed, managementFeeRate: mixed}> $envelopes */
        $envelopes = $this->validated('envelopes');

        return new MultiEnvelopeCalculationInputData(
            envelopes: array_map(
                fn (array $envelope): EnvelopeConfigData => new EnvelopeConfigData(
                    accountType: AccountType::from($envelope['accountType']),
                    initialAmount: (float) $envelope['initialAmount'],
                    monthlyContribution: (float) $envelope['monthlyContribution'],
                    durationYears: (int) $envelope['durationYears'],
                    annualReturnRate: ((float) $envelope['annualReturnRate']) / 100,
                    terRate: 0.0,
                    brokerageFeeRate: 0.0,
                    managementFeeRate: ((float) $envelope['managementFeeRate']) / 100,
                    custodyFeeRate: 0.0,
                    custodyFeeFixedMonthly: 0.0,
                    arbitrageFeeRate: 0.0,
                    arbitrageFeeFixed: 0.0,
                    inflationRate: $inflationRate,
                ),
                $envelopes,
            ),
        );
    }

    // Not part of MultiEnvelopeCalculationInputData: the scenario name is
    // storage metadata, not a financial-engine input, same reasoning as
    // RunSingleEnvelopeSimulationRequest::name().
    public function name(): ?string
    {
        return $this->string('name')->toString() ?: null;
    }
}
