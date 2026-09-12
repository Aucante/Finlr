<?php

namespace App\Modules\AnalogySimulator\Support;

use App\Modules\SimulationEngine\DTOs\AnalogyComparisonInputData;
use App\Modules\SimulationEngine\DTOs\AnalogyDeltaData;
use App\Modules\SimulationEngine\DTOs\AnalogyResultData;
use App\Modules\SimulationEngine\DTOs\AnalogyYearlyPointData;
use App\Modules\SimulationEngine\DTOs\CeilingEventData;
use App\Modules\SimulationEngine\DTOs\FiscalProfileData;

/**
 * Converts SimulationEngine's Analogy DTOs to plain arrays for storage in
 * Scenario::$input_payload/$result_payload — same reasoning as
 * MultiEnvelopeSimulator's own MultiEnvelopeScenarioPayload (Étape 2,
 * CONCEPTION.md §1.2): SimulationEngine's DTOs are reused as-is, not modified,
 * so no toArray() lives on them. Purely mechanical field-by-field mapping.
 */
final class AnalogyScenarioPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function input(AnalogyComparisonInputData $input): array
    {
        return [
            'accountTypeA' => $input->accountTypeA->value,
            'accountTypeB' => $input->accountTypeB->value,
            'initialAmount' => $input->initialAmount,
            'monthlyContribution' => $input->monthlyContribution,
            'durationYears' => $input->durationYears,
            'annualReturnRate' => $input->annualReturnRate,
            'terRate' => $input->terRate,
            'brokerageFeeRate' => $input->brokerageFeeRate,
            'managementFeeRate' => $input->managementFeeRate,
            'custodyFeeRate' => $input->custodyFeeRate,
            'custodyFeeFixedMonthly' => $input->custodyFeeFixedMonthly,
            'arbitrageFeeRate' => $input->arbitrageFeeRate,
            'arbitrageFeeFixed' => $input->arbitrageFeeFixed,
            'inflationRate' => $input->inflationRate,
            'labelA' => $input->labelA,
            'labelB' => $input->labelB,
            'fiscalProfile' => self::fiscalProfile($input->fiscalProfile),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function result(AnalogyResultData $result): array
    {
        return [
            'labelA' => $result->labelA,
            'labelB' => $result->labelB,
            'realNetBalanceWithInflation' => self::delta($result->realNetBalanceWithInflation),
            'netBalance' => self::delta($result->netBalance),
            'totalGains' => self::delta($result->totalGains),
            'taxesAmount' => self::delta($result->taxesAmount),
            'totalFees' => self::delta($result->totalFees),
            'totalDeposited' => self::delta($result->totalDeposited),
            'yearlyBreakdown' => array_map(self::yearlyPoint(...), $result->yearlyBreakdown),
            'finalLeader' => $result->finalLeader->value,
            'crossoverYears' => $result->crossoverYears,
            'hasCrossover' => $result->hasCrossover(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function fiscalProfile(FiscalProfileData $profile): array
    {
        return [
            'marginalIncomeTaxRate' => $profile->marginalIncomeTaxRate,
            'forcedRegime' => $profile->forcedRegime?->value,
            'isCoupleHousehold' => $profile->isCoupleHousehold,
            'socialLeviesStandard' => $profile->socialLeviesStandard,
            'socialLeviesReduced' => $profile->socialLeviesReduced,
            'flatTaxIncomeRate' => $profile->flatTaxIncomeRate,
            'lifeInsuranceReducedRate' => $profile->lifeInsuranceReducedRate,
            'lifeInsurancePremiumThreshold' => $profile->lifeInsurancePremiumThreshold,
        ];
    }

    /**
     * @return array{valueA: float, valueB: float, absolute: float, percent: float|null}
     */
    private static function delta(AnalogyDeltaData $delta): array
    {
        return [
            'valueA' => $delta->valueA,
            'valueB' => $delta->valueB,
            'absolute' => $delta->absolute,
            'percent' => $delta->percent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function yearlyPoint(AnalogyYearlyPointData $point): array
    {
        return [
            'year' => $point->year,
            'netBalance' => self::delta($point->netBalance),
            'realNetBalanceWithInflation' => self::delta($point->realNetBalanceWithInflation),
            'totalDeposited' => self::delta($point->totalDeposited),
            'leader' => $point->leader->value,
            'ceilingEventsA' => array_map(self::ceilingEvent(...), $point->ceilingEventsA),
            'ceilingEventsB' => array_map(self::ceilingEvent(...), $point->ceilingEventsB),
            'hasCeilingEvent' => $point->hasCeilingEvent(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function ceilingEvent(CeilingEventData $event): array
    {
        return [
            'accountType' => $event->accountType->value,
            'reachedAtMonth' => $event->reachedAtMonth,
            'ceiling' => $event->ceiling,
            'year' => $event->year,
            'isReachedOnInitialDeposit' => $event->isReachedOnInitialDeposit,
        ];
    }
}
