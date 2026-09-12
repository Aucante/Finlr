<?php

namespace App\Modules\Scenarios\Enums;

/**
 * Type de calculateur ayant produit un scénario sauvegardé.
 *
 * Chaque nouveau module simulateur (ex: SingleEnvelopeSimulator,
 * puis d'autres à venir) ajoute son propre cas ici au moment de sa
 * création. Le module Scenarios ne connaît jamais le contenu métier
 * associé à un cas : il se contente de le stocker et de le restituer.
 */
enum CalculatorType: string
{
    case SingleEnvelope = 'single_envelope';
    case MultiEnvelope = 'multi_envelope';
    case Analogy = 'analogy';
    case Fire = 'fire';

    /**
     * Frontend-facing i18n key for this case's label in the dashboard's
     * scenario list ("Type" column) — a translation key, not the display
     * text itself, so the actual wording stays centralised in
     * lang/{fr,en,it}.json (react-i18next), not duplicated here.
     */
    public function label(): string
    {
        return 'dashboard.scenarioList.calculatorTypes.'.$this->value;
    }

    /**
     * Extraction minimale des 3 champs affichés par la liste de scénarios
     * du dashboard, à partir des payloads bruts stockés en base — chaque
     * cas lit une forme de payload différente (miroir de son propre DTO
     * SimulationEngine), d'où le match() ici plutôt que dans
     * ScenarioSummaryData (CLAUDE.md : une règle qui dépend d'un cas
     * d'enum est une méthode de l'enum, jamais un match() dispersé).
     *
     * Généralisation complète : les 4 cas existants sont couverts, chacun
     * avec sa propre lecture du contrat à 3 champs (voir le cas Analogy
     * ci-dessous, qui n'a ni enveloppe ni horizon évidents, et le cas Fire,
     * qui n'a ni l'un ni l'autre non plus).
     *
     * @param  array<string, mixed>  $inputPayload
     * @param  array<string, mixed>  $resultPayload
     * @return array{headlineFigure: float, wrapper: string, years: int}
     */
    public function summarize(array $inputPayload, array $resultPayload): array
    {
        return match ($this) {
            self::SingleEnvelope => [
                'headlineFigure' => (float) ($resultPayload['finalNetReal'] ?? 0.0),
                'wrapper' => (string) ($inputPayload['wrapper'] ?? ''),
                'years' => (int) ($inputPayload['years'] ?? 0),
            ],
            // No single wrapper applies to a cascade of several envelope
            // types: left blank rather than picking one arbitrarily.
            // ScenarioList.tsx already renders an unrecognised wrapper as
            // "—", so this needs no frontend change to display correctly.
            self::MultiEnvelope => [
                'headlineFigure' => (float) (($resultPayload['summary'] ?? [])['netBalance'] ?? 0.0),
                'wrapper' => '',
                'years' => (int) (($resultPayload['summary'] ?? [])['year'] ?? 0),
            ],
            // Analogy has no single envelope either — "wrapper" becomes
            // "labelA vs labelB" (the two scenarios actually compared).
            // Unlike MultiEnvelope's blank string above, this is meant to
            // be shown as-is: ScenarioList.tsx's formatWrapper() was
            // extended (see CONCEPTION.md) to pass through any non-empty,
            // non-code wrapper string instead of collapsing it to "—" —
            // MultiEnvelope's own blank-string case is unaffected.
            // headlineFigure is the magnitude of the gap on the reference
            // metric (realNetBalanceWithInflation) — there is no single
            // "final balance" for a comparison, only how far apart the two
            // scenarios end up. years is the horizon actually compared
            // (length of yearlyBreakdown, contiguous 1..N).
            self::Analogy => [
                'headlineFigure' => abs((float) (($resultPayload['realNetBalanceWithInflation'] ?? [])['absolute'] ?? 0.0)),
                'wrapper' => sprintf(
                    '%s vs %s',
                    (string) ($resultPayload['labelA'] ?? ''),
                    (string) ($resultPayload['labelB'] ?? ''),
                ),
                'years' => count($resultPayload['yearlyBreakdown'] ?? []),
            ],
            // FIRE has no envelope either — blank wrapper, same convention
            // as MultiEnvelope. requiredCapital is always present (never
            // null, per FireProjectionResultData's own docblock), so it is
            // always a safe headlineFigure. yearsToRetirement is nullable
            // (target never reached on the package's internal horizon): 0
            // signals that case rather than a fabricated duration — chosen
            // deliberately because ScenarioList's formatHorizon() already
            // renders a non-positive years value as "—", exactly the
            // "not applicable" reading this case needs, with no frontend
            // change required.
            self::Fire => [
                'headlineFigure' => (float) ($resultPayload['requiredCapital'] ?? 0.0),
                'wrapper' => '',
                'years' => $resultPayload['yearsToRetirement'] === null
                    ? 0
                    : (int) round((float) $resultPayload['yearsToRetirement']),
            ],
        };
    }
}
