import type {
    CompoundInputs,
    CompoundPoint,
    CompoundResult,
} from '@/features/freemium-calculator/types';

/**
 * Derives a monthly rate from an annual one the same way
 * `saucante74/finlr-engine` has since v2.0.0: `(1 + annual)^(1/12) - 1`
 * (true monthly compounding), not `annual / 12` (simple mensualization).
 * The two diverge measurably — this collapses to the same value only at
 * annual = 0.
 */
function monthlyRateFromAnnualPercent(annualRatePercent: number): number {
    return (1 + annualRatePercent / 100) ** (1 / 12) - 1;
}

function buildPoint(
    year: number,
    contributions: number,
    grossCapital: number,
    netCapital: number,
    taxRate: number,
    inflationRate: number,
): CompoundPoint {
    const netGainsBeforeTax = netCapital - contributions;
    const netReal =
        contributions +
        (netGainsBeforeTax > 0
            ? netGainsBeforeTax * (1 - taxRate / 100)
            : netGainsBeforeTax);
    const inflationFactor = (1 + inflationRate / 100) ** year;
    const netRealAdjusted = inflationFactor > 0 ? netReal / inflationFactor : netReal;

    return {
        year,
        contributions,
        gross: grossCapital,
        netReal,
        netRealAdjusted,
    };
}

/**
 * Projects compound growth for the free, client-side calculator. This is a
 * deliberately simplified approximation, not a call into the real financial
 * engine — the calculator's whole value is instant, in-browser feedback as
 * the user types, which a server round-trip to `saucante74/finlr-engine`
 * would break. Two things follow from that:
 *
 * (a) What is simplified away, on purpose: real French capital-gains
 *     taxation is bracket- and wrapper-specific (see the PEA/CTO rules
 *     documented in `constants.ts` and `tests/Unit/SimulationEngine/
 *     FinlrEngineAdapterTest.php`); here it collapses to one flat
 *     `taxRate` applied to the whole net gain. Likewise, the premium
 *     engine's finer-grained fee model (brokerage, management, custody,
 *     arbitrage — each with its own rate, some with a fixed component too)
 *     collapses here to two flat annual percentages, `wrapperFee` and
 *     `fundFee`, subtracted straight from the gross rate.
 *
 * (b) This function is NOT wired to `saucante74/finlr-engine` and never
 *     will be — by design, not by oversight (see CLAUDE.md, "Journal de
 *     Décisions Produit"). Nothing keeps it in sync automatically: if the
 *     premium engine's rates or growth algorithm change, this one does not
 *     follow. Whether it's still a reasonable approximation after such a
 *     change is a judgment call for a human (or a future session) to make
 *     deliberately, not something either engine enforces.
 *
 * (c) Last verified consistent with current rates: 2026-09-07 (PEA/CTO
 *     rates in `constants.ts` cross-checked against
 *     `tests/Unit/SimulationEngine/FinlrEngineAdapterTest.php`, still
 *     matching: PEA preferential 18.6%, PEA standard/CTO 31.4%; monthly-rate
 *     derivation cross-checked against `saucante74/finlr-engine`'s
 *     CHANGELOG.md and aligned to its v2.0.0 compounding convention — see
 *     `monthlyRateFromAnnualPercent()` above). Update this date whenever
 *     that comparison is redone.
 */
export function computeCompound(inputs: CompoundInputs): CompoundResult {
    const {
        initialCapital,
        monthlyContribution,
        annualRate,
        years,
        wrapperFee,
        fundFee,
        taxRate,
        inflationRate,
        inflationEnabled,
    } = inputs;

    const safeYears = Math.max(0, Math.round(years) || 0);
    const months = safeYears * 12;

    const grossMonthlyRate = monthlyRateFromAnnualPercent(annualRate);
    const netAnnualRate = annualRate - wrapperFee - fundFee;
    const netMonthlyRate = monthlyRateFromAnnualPercent(netAnnualRate);

    let grossCapital = initialCapital;
    let netCapital = initialCapital;
    let contributions = initialCapital;

    const points = [
        buildPoint(0, contributions, grossCapital, netCapital, taxRate, inflationEnabled ? inflationRate : 0),
    ];

    for (let month = 1; month <= months; month += 1) {
        grossCapital = grossCapital * (1 + grossMonthlyRate) + monthlyContribution;
        netCapital = netCapital * (1 + netMonthlyRate) + monthlyContribution;
        contributions += monthlyContribution;

        if (month % 12 === 0) {
            points.push(
                buildPoint(
                    month / 12,
                    contributions,
                    grossCapital,
                    netCapital,
                    taxRate,
                    inflationEnabled ? inflationRate : 0,
                ),
            );
        }
    }

    const last = points[points.length - 1];
    const grossGains = last.gross - last.contributions;
    const netRealGains = last.netReal - last.contributions;

    return {
        points,
        invested: last.contributions,
        grossGains,
        finalGross: last.gross,
        netRealGains,
        finalNetReal: last.netReal,
        finalNetRealAdjusted: last.netRealAdjusted,
        shortfall: grossGains - netRealGains,
    };
}
