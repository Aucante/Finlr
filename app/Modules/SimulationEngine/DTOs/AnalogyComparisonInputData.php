<?php

namespace App\Modules\SimulationEngine\DTOs;

use App\Modules\SimulationEngine\Enums\AccountType;

/**
 * Input of AnalogyEngineInterface::compare(). Unlike the package's own
 * AnalogyInput (which takes two already-computed CalculationResult), this
 * DTO carries the shared simulation parameters (initial amount, DCA,
 * duration, fees, inflation, fiscal profile) requested from the user only
 * once, plus the single field that differs between the two scenarios: the
 * envelope type — the design validated in CONCEPTION.md §6.1, reproducing the
 * guarantee that vendor/saucante74/finlr-engine's own
 * manual_test_analogy.php enforces at the point of input (docs/API.md §3:
 * only the horizon is verified by the package itself, not the amount or
 * DCA — asking once, shared, makes divergence impossible by construction).
 *
 * $labelA/$labelB are deliberately required, with no hardcoded default: a
 * DTO must never carry user-facing text, that belongs to the i18n system
 * (lang/{fr,en,it}.json). The future controller/FormRequest that builds
 * this DTO must resolve them via t('simulator.analogy.defaultLabelA') /
 * t('simulator.analogy.defaultLabelB') — or the user's own scenario
 * labels, if provided — *before* constructing this DTO, not the other way
 * around.
 */
readonly class AnalogyComparisonInputData
{
    public function __construct(
        public AccountType $accountTypeA,
        public AccountType $accountTypeB,
        public float $initialAmount,
        public float $monthlyContribution,
        public int $durationYears,
        public float $annualReturnRate,
        public float $terRate,
        public float $brokerageFeeRate,
        public float $managementFeeRate,
        public float $custodyFeeRate,
        public float $custodyFeeFixedMonthly,
        public float $arbitrageFeeRate,
        public float $arbitrageFeeFixed,
        public float $inflationRate,
        public string $labelA,
        public string $labelB,
        public FiscalProfileData $fiscalProfile = new FiscalProfileData,
    ) {}
}
