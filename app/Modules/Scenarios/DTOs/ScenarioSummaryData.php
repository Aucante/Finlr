<?php

namespace App\Modules\Scenarios\DTOs;

use App\Modules\Scenarios\Enums\CalculatorType;
use App\Modules\Scenarios\Models\Scenario;
use Carbon\Carbon;

/**
 * Lightweight summary of a Scenario for list views (dashboard) that don't
 * need the full input/result payloads — those stay behind the existing
 * /scenarios/{id} detail page.
 */
readonly class ScenarioSummaryData
{
    public function __construct(
        public int $id,
        public CalculatorType $calculatorType,
        public float $headlineFigure,
        public ?Carbon $createdAt,
        public string $wrapper,
        public int $years,
        public ?string $name,
    ) {}

    public static function fromModel(Scenario $scenario): self
    {
        $summary = $scenario->calculator_type->summarize($scenario->input_payload, $scenario->result_payload);

        return new self(
            id: $scenario->id,
            calculatorType: $scenario->calculator_type,
            headlineFigure: $summary['headlineFigure'],
            createdAt: $scenario->created_at,
            wrapper: $summary['wrapper'],
            years: $summary['years'],
            name: $scenario->name,
        );
    }

    /**
     * @return array{id: int, calculatorType: string, typeLabel: string, headlineFigure: float, createdAt: string|null, wrapper: string, years: int, name: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'calculatorType' => $this->calculatorType->value,
            'typeLabel' => $this->calculatorType->label(),
            'headlineFigure' => $this->headlineFigure,
            'createdAt' => $this->createdAt?->toISOString(),
            'wrapper' => $this->wrapper,
            'years' => $this->years,
            'name' => $this->name,
        ];
    }
}
