<?php

namespace Tests\Unit\Scenarios;

use App\Modules\Scenarios\Enums\CalculatorType;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CalculatorTypeTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_label_returns_the_dedicated_i18n_key_for_each_case(CalculatorType $case, string $expected): void
    {
        $this->assertSame($expected, $case->label());
    }

    /**
     * @return array<string, array{0: CalculatorType, 1: string}>
     */
    public static function cases(): array
    {
        return [
            'single envelope' => [CalculatorType::SingleEnvelope, 'dashboard.scenarioList.calculatorTypes.single_envelope'],
            'multi envelope' => [CalculatorType::MultiEnvelope, 'dashboard.scenarioList.calculatorTypes.multi_envelope'],
            'analogy' => [CalculatorType::Analogy, 'dashboard.scenarioList.calculatorTypes.analogy'],
            'fire' => [CalculatorType::Fire, 'dashboard.scenarioList.calculatorTypes.fire'],
        ];
    }
}
