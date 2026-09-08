import { describe, expect, it } from 'vitest';

import { computeCompound } from '@/features/freemium-calculator/lib/compound';
import type { CompoundInputs } from '@/features/freemium-calculator/types';

/**
 * Every expected value in this file is derived from the closed-form ordinary
 * annuity formula, never from running computeCompound():
 *
 *     FV = P(1 + i)^n + PMT * ((1 + i)^n - 1) / i
 *     with i = (1 + rate/100)^(1/12) - 1, n = years*12
 *
 * `i` is a true monthly-compounding rate (matches `saucante74/finlr-engine`
 * since its v2.0.0 — see `monthlyRateFromAnnualPercent()` in compound.ts),
 * not the naive `rate/100/12`. One consequence worth remembering when
 * re-deriving any of these by hand: because `(1+i)^12 = 1 + rate/100`
 * exactly by construction, a lump sum with no contribution lands on exactly
 * `P * (1 + rate/100)^years` at every year boundary — no float noise, no
 * approximation — which is why a few figures below (e.g. 11910.16) are
 * clean decimals instead of the usual long tail.
 *
 * "Ordinary" (payment at the end of each period) is the right form here because
 * the implementation credits interest first and adds the monthly contribution
 * after, so a month's payment earns nothing during that month.
 *
 * The reference numbers below were computed with 50-digit decimal arithmetic
 * (Python's `decimal` module) and are quoted to 10 decimals; the derivation
 * is written above each one so it can be re-checked by hand. Each one was
 * also cross-checked against a direct Python simulation of the same
 * month-by-month loop as compound.ts, to rule out a closed-form transcription
 * error — both agreed to within 1e-11 on values around 4.5e4.
 *
 * Tolerance: comparisons use toBeCloseTo(expected, PRECISION_DIGITS), i.e. an
 * absolute tolerance of 5e-7. Rationale: the implementation iterates up to 120
 * multiply-adds in float64 while the reference uses a single exponentiation, so
 * the two paths cannot agree bit for bit. The accumulated drift is on the order
 * of 1e-10 on values around 4.5e4 -- roughly a thousand times smaller than this
 * tolerance, which is itself far below a cent. Tight enough to catch any real
 * formula error, loose enough to ignore float64 round-off.
 */
const PRECISION_DIGITS = 6;

function makeInputs(overrides: Partial<CompoundInputs> = {}): CompoundInputs {
    return {
        initialCapital: 0,
        monthlyContribution: 0,
        annualRate: 0,
        years: 1,
        wrapperFee: 0,
        fundFee: 0,
        taxRate: 0,
        inflationRate: 0,
        inflationEnabled: false,
        ...overrides,
    };
}

describe('computeCompound', () => {
    describe('capital growth', () => {
        it('grows an initial capital alone, with no monthly contribution', () => {
            // P = 10000, PMT = 0, r = 6%, y = 3  ->  i = 1.06^(1/12) - 1, n = 36
            // (1+i)^36 = 1.06^3 exactly (see file docblock), so:
            // FV = 10000 * 1.06^3 = 11910.16
            const result = computeCompound(
                makeInputs({ initialCapital: 10000, annualRate: 6, years: 3 }),
            );

            expect(result.finalGross).toBeCloseTo(11910.16, PRECISION_DIGITS);
            expect(result.invested).toBe(10000);
            expect(result.grossGains).toBeCloseTo(1910.16, PRECISION_DIGITS);
        });

        it('grows monthly contributions alone, with no initial capital', () => {
            // P = 0, PMT = 100, r = 12%, y = 1  ->  i = 1.12^(1/12) - 1, n = 12
            // FV = 100 * ((1+i)^12 - 1) / i = 100 * (1.12 - 1) / i = 1264.6497908353
            const result = computeCompound(
                makeInputs({ monthlyContribution: 100, annualRate: 12, years: 1 }),
            );

            expect(result.finalGross).toBeCloseTo(1264.6497908353, PRECISION_DIGITS);
            expect(result.invested).toBe(1200);
            expect(result.grossGains).toBeCloseTo(64.6497908353, PRECISION_DIGITS);
        });

        it('combines an initial capital and monthly contributions', () => {
            // P = 5000, PMT = 200, r = 7%, y = 10  ->  i = 1.07^(1/12) - 1, n = 120
            // FV = 5000 * (1+i)^120 + 200 * ((1+i)^120 - 1) / i = 44046.1030376749
            // invested = 5000 + 200 * 120 = 29000
            const result = computeCompound(
                makeInputs({
                    initialCapital: 5000,
                    monthlyContribution: 200,
                    annualRate: 7,
                    years: 10,
                }),
            );

            expect(result.finalGross).toBeCloseTo(44046.1030376749, PRECISION_DIGITS);
            expect(result.invested).toBe(29000);
            expect(result.grossGains).toBeCloseTo(15046.1030376749, PRECISION_DIGITS);
        });
    });

    describe('edge cases', () => {
        it('returns the initial capital and a single point when years is 0', () => {
            // n = 0: the loop never runs, so only the year-0 point exists and
            // FV = P * (1+i)^0 = P, whatever the rate or the contribution.
            const result = computeCompound(
                makeInputs({
                    initialCapital: 10000,
                    monthlyContribution: 500,
                    annualRate: 8,
                    years: 0,
                }),
            );

            expect(result.points).toHaveLength(1);
            expect(result.points[0].year).toBe(0);
            expect(result.finalGross).toBe(10000);
            expect(result.invested).toBe(10000);
            expect(result.grossGains).toBe(0);
            expect(result.finalNetReal).toBe(10000);
        });

        it('returns exactly the contributions and no gain when the rate is 0', () => {
            // i = 0: FV degenerates to P + PMT * n = 1000 + 100 * 24 = 3400
            const result = computeCompound(
                makeInputs({
                    initialCapital: 1000,
                    monthlyContribution: 100,
                    annualRate: 0,
                    years: 2,
                }),
            );

            expect(result.finalGross).toBe(3400);
            expect(result.invested).toBe(3400);
            expect(result.grossGains).toBe(0);
            expect(result.netRealGains).toBe(0);
        });
    });

    describe('fees', () => {
        it('lowers the net result without touching the gross one', () => {
            // Gross uses r = 6%. Net uses r - wrapperFee - fundFee = 6 - 0.5 - 0.3 = 5.2%.
            // gross = 10000 * 1.06^3                      = 11910.16
            // net   = 10000 * (1.052)^(1/12 * 36 = 3)     = 11642.526080 (see file docblock)
            // With taxRate = 0 the net capital passes through untaxed.
            const withFees = computeCompound(
                makeInputs({
                    initialCapital: 10000,
                    annualRate: 6,
                    years: 3,
                    wrapperFee: 0.5,
                    fundFee: 0.3,
                }),
            );
            const withoutFees = computeCompound(
                makeInputs({ initialCapital: 10000, annualRate: 6, years: 3 }),
            );

            expect(withFees.finalGross).toBeCloseTo(11910.16, PRECISION_DIGITS);
            expect(withFees.finalGross).toBeCloseTo(withoutFees.finalGross, PRECISION_DIGITS);

            expect(withFees.finalNetReal).toBeCloseTo(11642.5260800000, PRECISION_DIGITS);
            expect(withoutFees.finalNetReal).toBeCloseTo(11910.16, PRECISION_DIGITS);
            expect(withFees.finalNetReal).toBeLessThan(withoutFees.finalNetReal);
        });
    });

    describe('tax', () => {
        it('applies the tax rate to a positive capital gain', () => {
            // No fee, so net capital = gross capital = 11910.16 (see docblock).
            // gain = 1910.16
            // netReal = 10000 + gain * (1 - 30/100) = 10000 + 1337.112
            //         = 11337.112
            const result = computeCompound(
                makeInputs({
                    initialCapital: 10000,
                    annualRate: 6,
                    years: 3,
                    taxRate: 30,
                }),
            );

            expect(result.grossGains).toBeCloseTo(1910.16, PRECISION_DIGITS);
            expect(result.finalNetReal).toBeCloseTo(11337.112, PRECISION_DIGITS);
            expect(result.netRealGains).toBeCloseTo(1337.112, PRECISION_DIGITS);
        });

        it('leaves a negative capital gain untaxed', () => {
            // r = 0 and wrapperFee = 6 give a net rate of -6%/year, so the net
            // capital falls below the contributions. At year boundaries this
            // collapses to P * (1 - 6/100)^years exactly (see docblock):
            //   net = 10000 * 0.94^3 = 8305.84
            // The loss is -1694.16. A 30% tax on it would ADD 508.25 back;
            // the expected behaviour is that no tax applies at all, so
            // netReal = net capital exactly.
            const result = computeCompound(
                makeInputs({
                    initialCapital: 10000,
                    annualRate: 0,
                    years: 3,
                    wrapperFee: 6,
                    taxRate: 30,
                }),
            );

            expect(result.finalNetReal).toBeCloseTo(8305.84, PRECISION_DIGITS);
            expect(result.netRealGains).toBeCloseTo(-1694.16, PRECISION_DIGITS);
            // Gross is driven by annualRate = 0, so it stays flat at the capital.
            expect(result.finalGross).toBe(10000);
        });
    });

    describe('inflation', () => {
        it('deflates netRealAdjusted by the compounded inflation rate', () => {
            // netReal (see the tax test above)      = 11337.112
            // 1.02^3                                = 1.061208
            // netRealAdjusted = netReal / 1.02^3    = 10683.2138468613
            const result = computeCompound(
                makeInputs({
                    initialCapital: 10000,
                    annualRate: 6,
                    years: 3,
                    taxRate: 30,
                    inflationRate: 2,
                    inflationEnabled: true,
                }),
            );

            expect(result.finalNetReal).toBeCloseTo(11337.112, PRECISION_DIGITS);
            expect(result.finalNetRealAdjusted).toBeCloseTo(10683.2138468613, PRECISION_DIGITS);
        });

        it('leaves netRealAdjusted equal to netReal when inflation is disabled', () => {
            // inflationEnabled: false means no deflation is applied, so the
            // adjusted figure must collapse back onto the nominal net figure:
            //   netRealAdjusted = netReal = 11337.112
            // (Same inputs as the test above, only the flag differs.)
            const result = computeCompound(
                makeInputs({
                    initialCapital: 10000,
                    annualRate: 6,
                    years: 3,
                    taxRate: 30,
                    inflationRate: 2,
                    inflationEnabled: false,
                }),
            );

            expect(result.finalNetReal).toBeCloseTo(11337.112, PRECISION_DIGITS);
            expect(result.finalNetRealAdjusted).toBeCloseTo(11337.112, PRECISION_DIGITS);
        });
    });

    describe('shortfall', () => {
        it('equals grossGains minus netRealGains', () => {
            // gross gain 1910.16, taxed at 30% -> net gain 1337.112
            // shortfall = 1910.16 - 1337.112 = 573.048
            //           = 1910.16 * 0.30
            const result = computeCompound(
                makeInputs({
                    initialCapital: 10000,
                    annualRate: 6,
                    years: 3,
                    taxRate: 30,
                }),
            );

            expect(result.shortfall).toBeCloseTo(573.048, PRECISION_DIGITS);
            expect(result.shortfall).toBeCloseTo(
                result.grossGains - result.netRealGains,
                PRECISION_DIGITS,
            );
        });
    });

    describe('points', () => {
        it('returns one point per year plus year 0, indexed from 0', () => {
            // y = 5 -> 60 months, a point pushed every 12th month (5 of them)
            // plus the initial point => 6 points, years 0..5 in order.
            const result = computeCompound(
                makeInputs({
                    initialCapital: 1000,
                    monthlyContribution: 100,
                    annualRate: 5,
                    years: 5,
                }),
            );

            expect(result.points).toHaveLength(6);
            expect(result.points.map((p) => p.year)).toEqual([0, 1, 2, 3, 4, 5]);
        });

        it('starts from the initial capital at year 0', () => {
            // Year 0 is taken before any interest or contribution, so all four
            // series equal the initial capital and contributions equal P.
            const result = computeCompound(
                makeInputs({
                    initialCapital: 1000,
                    monthlyContribution: 100,
                    annualRate: 5,
                    years: 5,
                }),
            );

            expect(result.points[0]).toEqual({
                year: 0,
                contributions: 1000,
                gross: 1000,
                netReal: 1000,
                netRealAdjusted: 1000,
            });
        });

        it('carries the last point into the summary figures', () => {
            // P = 5000, PMT = 200, r = 7%, y = 10 (same as the combined test):
            // the last point is year 10 and must hold the final gross figure.
            const result = computeCompound(
                makeInputs({
                    initialCapital: 5000,
                    monthlyContribution: 200,
                    annualRate: 7,
                    years: 10,
                }),
            );

            const last = result.points[result.points.length - 1];

            expect(last.year).toBe(10);
            expect(last.gross).toBeCloseTo(44046.1030376749, PRECISION_DIGITS);
            expect(last.contributions).toBe(29000);
            expect(result.finalGross).toBe(last.gross);
            expect(result.invested).toBe(last.contributions);
        });
    });
});
