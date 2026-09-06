<?php

namespace Tests\Unit\Scenarios;

use App\Modules\Scenarios\DTOs\ScenarioSummaryData;
use App\Modules\Scenarios\Enums\CalculatorType;
use App\Modules\Scenarios\Models\Scenario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScenarioSummaryDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_from_model_extracts_the_seven_expected_fields(): void
    {
        $scenario = Scenario::factory()->create([
            'calculator_type' => CalculatorType::SingleEnvelope,
            'name' => 'Retraite à 62 ans',
            'input_payload' => $this->realisticInputPayload(),
            'result_payload' => $this->realisticResultPayload(),
        ]);

        $summary = ScenarioSummaryData::fromModel($scenario);

        $this->assertSame($scenario->id, $summary->id);
        $this->assertSame(CalculatorType::SingleEnvelope, $summary->calculatorType);
        $this->assertSame(31234.56, $summary->headlineFigure);
        $this->assertTrue($scenario->created_at->equalTo($summary->createdAt));
        $this->assertSame('pea', $summary->wrapper);
        $this->assertSame(15, $summary->years);
        $this->assertSame('Retraite à 62 ans', $summary->name);
    }

    public function test_to_array_produces_the_expected_shape(): void
    {
        $scenario = Scenario::factory()->create([
            'calculator_type' => CalculatorType::SingleEnvelope,
            'name' => 'Retraite à 62 ans',
            'input_payload' => $this->realisticInputPayload(),
            'result_payload' => $this->realisticResultPayload(),
        ]);

        $array = ScenarioSummaryData::fromModel($scenario)->toArray();

        $this->assertSame([
            'id' => $scenario->id,
            'calculatorType' => 'single_envelope',
            'typeLabel' => 'dashboard.scenarioList.calculatorTypes.single_envelope',
            'headlineFigure' => 31234.56,
            'createdAt' => $scenario->created_at->toISOString(),
            'wrapper' => 'pea',
            'years' => 15,
            'name' => 'Retraite à 62 ans',
        ], $array);
    }

    public function test_from_model_defaults_wrapper_and_years_when_missing_from_input_payload(): void
    {
        $scenario = Scenario::factory()->create([
            'calculator_type' => CalculatorType::SingleEnvelope,
            'input_payload' => [],
            'result_payload' => $this->realisticResultPayload(),
        ]);

        $summary = ScenarioSummaryData::fromModel($scenario);

        $this->assertSame('', $summary->wrapper);
        $this->assertSame(0, $summary->years);
    }

    public function test_a_scenario_without_a_name_serializes_with_name_null(): void
    {
        $scenario = Scenario::factory()->create([
            'calculator_type' => CalculatorType::SingleEnvelope,
            'input_payload' => $this->realisticInputPayload(),
            'result_payload' => $this->realisticResultPayload(),
        ]);

        $this->assertNull($scenario->name);

        $summary = ScenarioSummaryData::fromModel($scenario);

        $this->assertNull($summary->name);
        $this->assertArrayHasKey('name', $summary->toArray());
        $this->assertNull($summary->toArray()['name']);
    }

    public function test_from_model_extracts_a_multi_envelope_scenario_with_no_single_wrapper(): void
    {
        $scenario = Scenario::factory()->create([
            'calculator_type' => CalculatorType::MultiEnvelope,
            'name' => 'Cascade PEA + CTO',
            'input_payload' => $this->realisticMultiEnvelopeInputPayload(),
            'result_payload' => $this->realisticMultiEnvelopeResultPayload(),
        ]);

        $summary = ScenarioSummaryData::fromModel($scenario);

        $this->assertSame(CalculatorType::MultiEnvelope, $summary->calculatorType);
        $this->assertSame(26628.0, $summary->headlineFigure);
        // No single envelope type applies to a cascade: left blank rather
        // than picking one of the envelopes arbitrarily.
        $this->assertSame('', $summary->wrapper);
        $this->assertSame(10, $summary->years);
    }

    public function test_from_model_extracts_an_analogy_scenario_with_the_label_pair_as_wrapper(): void
    {
        $scenario = Scenario::factory()->create([
            'calculator_type' => CalculatorType::Analogy,
            'name' => 'PEA vs CTO à 20 ans',
            'input_payload' => $this->realisticAnalogyInputPayload(),
            'result_payload' => $this->realisticAnalogyResultPayload(),
        ]);

        $summary = ScenarioSummaryData::fromModel($scenario);

        $this->assertSame(CalculatorType::Analogy, $summary->calculatorType);
        // Magnitude of the gap on the reference metric, not a "final
        // balance" — a comparison has none.
        $this->assertSame(5000.11, $summary->headlineFigure);
        $this->assertSame('PEA plafonné vs CTO sans plafond', $summary->wrapper);
        $this->assertSame(20, $summary->years);
    }

    public function test_from_model_extracts_a_fire_scenario_using_required_capital_as_headline(): void
    {
        $scenario = Scenario::factory()->create([
            'calculator_type' => CalculatorType::Fire,
            'name' => 'Indépendance à 55 ans',
            'input_payload' => $this->realisticFireInputPayload(),
            'result_payload' => $this->realisticFireResultPayload(),
        ]);

        $summary = ScenarioSummaryData::fromModel($scenario);

        $this->assertSame(CalculatorType::Fire, $summary->calculatorType);
        $this->assertSame(500_000.0, $summary->headlineFigure);
        // No envelope applies to FIRE either: same blank-wrapper convention
        // as MultiEnvelope.
        $this->assertSame('', $summary->wrapper);
        $this->assertSame(25, $summary->years);
    }

    public function test_from_model_extracts_a_fire_scenario_with_a_never_reached_target_as_zero_years(): void
    {
        $scenario = Scenario::factory()->create([
            'calculator_type' => CalculatorType::Fire,
            'input_payload' => $this->realisticFireInputPayload(),
            'result_payload' => array_merge($this->realisticFireResultPayload(), [
                'retirementAge' => null,
                'yearsToRetirement' => null,
            ]),
        ]);

        $summary = ScenarioSummaryData::fromModel($scenario);

        $this->assertSame(0, $summary->years);
    }

    /**
     * @return array<string, mixed>
     */
    private function realisticFireInputPayload(): array
    {
        return [
            'currentAge' => 30,
            'currentCapital' => 10_000.0,
            'monthlyContribution' => 500.0,
            'annualReturnRate' => 0.06,
            'desiredAnnualIncome' => 20_000.0,
            'withdrawalRate' => 4.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function realisticFireResultPayload(): array
    {
        return [
            'requiredCapital' => 500_000.0,
            'retirementAge' => 55.0,
            'yearsToRetirement' => 25.0,
            'optimistic' => ['requiredCapital' => 400_000.0, 'retirementAge' => 52.0, 'yearsToRetirement' => 22.0],
            'neutral' => ['requiredCapital' => 500_000.0, 'retirementAge' => 55.0, 'yearsToRetirement' => 25.0],
            'pessimistic' => ['requiredCapital' => 666_666.67, 'retirementAge' => 59.0, 'yearsToRetirement' => 29.0],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function realisticAnalogyInputPayload(): array
    {
        return [
            'accountTypeA' => 'PEA',
            'accountTypeB' => 'CTO',
            'labelA' => 'PEA plafonné',
            'labelB' => 'CTO sans plafond',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function realisticAnalogyResultPayload(): array
    {
        return [
            'labelA' => 'PEA plafonné',
            'labelB' => 'CTO sans plafond',
            'realNetBalanceWithInflation' => ['valueA' => 100000.11, 'valueB' => 105000.22, 'absolute' => 5000.11, 'percent' => 0.05],
            'yearlyBreakdown' => array_fill(0, 20, ['year' => 1]),
            'finalLeader' => 'SCENARIO_A',
            'crossoverYears' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function realisticMultiEnvelopeInputPayload(): array
    {
        return [
            'envelopes' => [
                ['accountType' => 'PEA', 'initialAmount' => 1000.0],
                ['accountType' => 'CTO', 'initialAmount' => 0.0],
            ],
            'defaultOverflowAccountType' => 'COMPTE_COURANT',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function realisticMultiEnvelopeResultPayload(): array
    {
        return [
            'summary' => ['year' => 10, 'netBalance' => 26628.0],
            'pockets' => [],
        ];
    }

    /**
     * A production input_payload, keyed like CalculationInputData::toArray().
     *
     * @return array<string, mixed>
     */
    private function realisticInputPayload(): array
    {
        return [
            'initialCapital' => 10000.0,
            'monthlyContribution' => 200.0,
            'annualRate' => 5.0,
            'years' => 15,
            'wrapperFee' => 0.6,
            'fundFee' => 0.2,
            'taxRate' => 30.0,
            'inflationRate' => 2.0,
            'inflationEnabled' => true,
            'wrapper' => 'pea',
        ];
    }

    /**
     * A production result_payload, keyed like CalculationResultData::toArray()
     * (camelCase) — unlike ScenarioFactory's own default, which predates
     * this shape and still uses snake_case keys.
     *
     * @return array<string, mixed>
     */
    private function realisticResultPayload(): array
    {
        return [
            'points' => [],
            'invested' => 25000.0,
            'grossGains' => 9567.89,
            'finalGross' => 34567.89,
            'netRealGains' => 6234.56,
            'finalNetReal' => 31234.56,
            'finalNetRealAdjusted' => 29000.12,
            'shortfall' => 3333.33,
        ];
    }
}
