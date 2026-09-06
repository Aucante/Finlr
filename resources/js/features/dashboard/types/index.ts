export type CalculatorType = 'single_envelope' | 'multi_envelope' | 'analogy' | 'fire';

export interface ScenarioSummary {
    id: number;
    calculatorType: CalculatorType;
    // i18n key for the "Type" column (CalculatorType::label() on the
    // backend) — never a literal label, so the frontend just passes it to
    // t() and never matches on calculatorType itself.
    typeLabel: string;
    headlineFigure: number;
    createdAt: string | null;
    // Mirrors the backend DTO's plain `string`: legacy scenarios may carry
    // an empty string, or (historically) a wrapper no longer offered.
    wrapper: string;
    years: number;
    name: string | null;
}

export interface DashboardPageProps {
    scenarios: ScenarioSummary[];
}
