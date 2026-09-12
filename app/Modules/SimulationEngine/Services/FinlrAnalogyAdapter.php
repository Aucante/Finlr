<?php

namespace App\Modules\SimulationEngine\Services;

use App\Modules\SimulationEngine\Contracts\AnalogyEngineInterface;
use App\Modules\SimulationEngine\DTOs\AnalogyComparisonInputData;
use App\Modules\SimulationEngine\DTOs\AnalogyDeltaData;
use App\Modules\SimulationEngine\DTOs\AnalogyResultData;
use App\Modules\SimulationEngine\DTOs\AnalogyYearlyPointData;
use App\Modules\SimulationEngine\DTOs\CeilingEventData;
use App\Modules\SimulationEngine\DTOs\FiscalProfileData;
use App\Modules\SimulationEngine\Enums\AccountType;
use App\Modules\SimulationEngine\Enums\AnalogyLeader;
use saucante74\CalculatorEngine\Analogy\Actions\CompareCalculationResults;
use saucante74\CalculatorEngine\Analogy\DTOs\AnalogyDelta as PackageAnalogyDelta;
use saucante74\CalculatorEngine\Analogy\DTOs\AnalogyInput as PackageAnalogyInput;
use saucante74\CalculatorEngine\Analogy\DTOs\AnalogyResult as PackageAnalogyResult;
use saucante74\CalculatorEngine\Analogy\DTOs\AnalogyYearlyPoint as PackageAnalogyYearlyPoint;
use saucante74\CalculatorEngine\Analogy\DTOs\CeilingEvent as PackageCeilingEvent;
use saucante74\CalculatorEngine\Simulators\France\SimulatorMultiEnvelope\Actions\MultiEnvelopeSimulator;
use saucante74\CalculatorEngine\Simulators\France\SimulatorMultiEnvelope\DTOs\CalculationResult as PackageCalculationResult;
use saucante74\CalculatorEngine\Simulators\France\SimulatorMultiEnvelope\DTOs\EnvelopeConfig as PackageEnvelopeConfig;
use saucante74\CalculatorEngine\Strategies\FiscalProfile as PackageFiscalProfile;
use saucante74\CalculatorEngine\Strategies\FiscalRates as PackageFiscalRates;

/**
 * Adapter around
 * saucante74\CalculatorEngine\Analogy\Actions\CompareCalculationResults::compare()
 * (docs/API.md §3): builds both single-envelope scenarios from the shared
 * parameters carried by AnalogyComparisonInputData, then compares them.
 *
 * `defaultOverflowAccountType: null` is passed explicitly to each cascade
 * (CONCEPTION.md §6.1): injecting a default overflow pocket would make both
 * scenarios absorb the same total amount regardless of which envelope
 * saturates, erasing the very "amount actually invested" gap the
 * comparison exists to reveal (docs/API.md §3, totalDeposited).
 */
class FinlrAnalogyAdapter implements AnalogyEngineInterface
{
    public function __construct(
        private readonly MultiEnvelopeSimulator $multiEnvelopeEngine,
        private readonly CompareCalculationResults $comparator,
    ) {}

    public function compare(AnalogyComparisonInputData $input): AnalogyResultData
    {
        $fiscalProfile = $this->toPackageFiscalProfile($input->fiscalProfile);

        $scenarioA = $this->runScenario($input, $input->accountTypeA, $fiscalProfile);
        $scenarioB = $this->runScenario($input, $input->accountTypeB, $fiscalProfile);

        $result = $this->comparator->compare(new PackageAnalogyInput(
            scenarioA: $scenarioA,
            scenarioB: $scenarioB,
            labelA: $input->labelA,
            labelB: $input->labelB,
        ));

        return $this->toAnalogyResultData($result);
    }

    private function runScenario(
        AnalogyComparisonInputData $input,
        AccountType $accountType,
        PackageFiscalProfile $fiscalProfile,
    ): PackageCalculationResult {
        return $this->multiEnvelopeEngine->calculateIndependentCascade(
            envelopeConfigs: [$this->toPackageEnvelopeConfig($input, $accountType)],
            defaultOverflowAccountType: null,
            fiscalProfile: $fiscalProfile,
        );
    }

    private function toPackageEnvelopeConfig(AnalogyComparisonInputData $input, AccountType $accountType): PackageEnvelopeConfig
    {
        return new PackageEnvelopeConfig(
            accountType: $accountType->toPackage(),
            initialAmount: $input->initialAmount,
            monthlyContribution: $input->monthlyContribution,
            durationYears: $input->durationYears,
            annualReturnRate: $input->annualReturnRate,
            terRate: $input->terRate,
            brokerageFeeRate: $input->brokerageFeeRate,
            managementFeeRate: $input->managementFeeRate,
            custodyFeeRate: $input->custodyFeeRate,
            custodyFeeFixedMonthly: $input->custodyFeeFixedMonthly,
            arbitrageFeeRate: $input->arbitrageFeeRate,
            arbitrageFeeFixed: $input->arbitrageFeeFixed,
            inflationRate: $input->inflationRate,
        );
    }

    private function toPackageFiscalProfile(FiscalProfileData $profile): PackageFiscalProfile
    {
        return new PackageFiscalProfile(
            marginalIncomeTaxRate: $profile->marginalIncomeTaxRate,
            forcedRegime: $profile->forcedRegime?->toPackage(),
            isCoupleHousehold: $profile->isCoupleHousehold,
            socialLeviesStandard: $profile->socialLeviesStandard ?? PackageFiscalRates::SOCIAL_LEVIES_STANDARD,
            socialLeviesReduced: $profile->socialLeviesReduced ?? PackageFiscalRates::SOCIAL_LEVIES_REDUCED,
            flatTaxIncomeRate: $profile->flatTaxIncomeRate ?? PackageFiscalRates::FLAT_TAX_INCOME_RATE,
            lifeInsuranceReducedRate: $profile->lifeInsuranceReducedRate ?? PackageFiscalRates::LIFE_INSURANCE_REDUCED_INCOME_RATE,
            lifeInsurancePremiumThreshold: $profile->lifeInsurancePremiumThreshold ?? PackageFiscalRates::LIFE_INSURANCE_PREMIUM_THRESHOLD,
        );
    }

    private function toAnalogyResultData(PackageAnalogyResult $result): AnalogyResultData
    {
        return new AnalogyResultData(
            labelA: $result->labelA,
            labelB: $result->labelB,
            realNetBalanceWithInflation: $this->toAnalogyDeltaData($result->realNetBalanceWithInflation),
            netBalance: $this->toAnalogyDeltaData($result->netBalance),
            totalGains: $this->toAnalogyDeltaData($result->totalGains),
            taxesAmount: $this->toAnalogyDeltaData($result->taxesAmount),
            totalFees: $this->toAnalogyDeltaData($result->totalFees),
            totalDeposited: $this->toAnalogyDeltaData($result->totalDeposited),
            yearlyBreakdown: array_map($this->toAnalogyYearlyPointData(...), $result->yearlyBreakdown),
            finalLeader: AnalogyLeader::fromPackage($result->finalLeader),
            crossoverYears: $result->crossoverYears,
        );
    }

    private function toAnalogyDeltaData(PackageAnalogyDelta $delta): AnalogyDeltaData
    {
        return new AnalogyDeltaData(
            valueA: $delta->valueA,
            valueB: $delta->valueB,
            absolute: $delta->absolute,
            percent: $delta->percent,
        );
    }

    private function toAnalogyYearlyPointData(PackageAnalogyYearlyPoint $point): AnalogyYearlyPointData
    {
        return new AnalogyYearlyPointData(
            year: $point->year,
            netBalance: $this->toAnalogyDeltaData($point->netBalance),
            realNetBalanceWithInflation: $this->toAnalogyDeltaData($point->realNetBalanceWithInflation),
            totalDeposited: $this->toAnalogyDeltaData($point->totalDeposited),
            leader: AnalogyLeader::fromPackage($point->leader),
            ceilingEventsA: array_map($this->toCeilingEventData(...), $point->ceilingEventsA),
            ceilingEventsB: array_map($this->toCeilingEventData(...), $point->ceilingEventsB),
        );
    }

    private function toCeilingEventData(PackageCeilingEvent $event): CeilingEventData
    {
        return new CeilingEventData(
            accountType: AccountType::fromPackage($event->accountType),
            reachedAtMonth: $event->reachedAtMonth,
            ceiling: $event->ceiling,
            year: $event->year(),
            isReachedOnInitialDeposit: $event->isReachedOnInitialDeposit(),
        );
    }
}
