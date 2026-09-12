<?php

namespace App\Modules\Auth\Actions;

/**
 * Deliberately a coarse summary, not an exhaustive User-Agent parser: the
 * label only helps a user tell their own devices apart in /settings.
 *
 * The label only ever contains product names ("Chrome", "Windows"), joined
 * by a language-neutral separator — never a translated word like "sur" or
 * "on": it is stored once, at issuance, and must read correctly in every
 * locale the interface is later displayed in. Null means "unrecognised";
 * the translated fallback lives in the frontend.
 */
class DeriveTrustedDeviceLabelAction
{
    public const SEPARATOR = ' · ';

    /**
     * Order matters: several browsers embed another one's token (Edge and
     * Opera both advertise "Chrome/", every Chromium browser advertises
     * "Safari/"), so the most specific pattern must be tried first.
     */
    private const BROWSERS = [
        '/\bEdg(e|A|iOS)?\//' => 'Edge',
        '/\b(OPR|Opera)\//' => 'Opera',
        '/\bSamsungBrowser\//' => 'Samsung Internet',
        '/\b(Firefox|FxiOS)\//' => 'Firefox',
        '/\b(Chrome|CriOS|Chromium)\//' => 'Chrome',
        '/\bVersion\/[\d.]+.*\bSafari\//' => 'Safari',
    ];

    /**
     * Same ordering constraint: Android advertises "Linux", and iOS
     * devices advertise "like Mac OS X".
     */
    private const PLATFORMS = [
        '/\biPhone\b/' => 'iPhone',
        '/\biPad\b/' => 'iPad',
        '/\bAndroid\b/' => 'Android',
        '/\bWindows\b/' => 'Windows',
        '/\bCrOS\b/' => 'ChromeOS',
        '/\bMacintosh\b/' => 'macOS',
        '/\bLinux\b/' => 'Linux',
    ];

    public function handle(?string $userAgent): ?string
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return null;
        }

        $parts = array_filter([
            $this->firstMatch(self::BROWSERS, $userAgent),
            $this->firstMatch(self::PLATFORMS, $userAgent),
        ]);

        return $parts === [] ? null : implode(self::SEPARATOR, $parts);
    }

    /**
     * @param  array<string, string>  $patterns
     */
    private function firstMatch(array $patterns, string $userAgent): ?string
    {
        foreach ($patterns as $pattern => $name) {
            if (preg_match($pattern, $userAgent) === 1) {
                return $name;
            }
        }

        return null;
    }
}
