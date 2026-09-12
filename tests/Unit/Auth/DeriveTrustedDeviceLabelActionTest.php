<?php

namespace Tests\Unit\Auth;

use App\Modules\Auth\Actions\DeriveTrustedDeviceLabelAction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DeriveTrustedDeviceLabelActionTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function recognisedUserAgentProvider(): array
    {
        return [
            'Chrome on Windows' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                'Chrome · Windows',
            ],
            'Safari on iPhone' => [
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
                'Safari · iPhone',
            ],
            'Safari on macOS' => [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
                'Safari · macOS',
            ],
            'Firefox on Linux' => [
                'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:127.0) Gecko/20100101 Firefox/127.0',
                'Firefox · Linux',
            ],
            'Edge on Windows (advertises Chrome/ too)' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.2592.87',
                'Edge · Windows',
            ],
            'Opera on macOS (advertises Chrome/ too)' => [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 OPR/112.0.0.0',
                'Opera · macOS',
            ],
            'Chrome on Android (advertises Linux too)' => [
                'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36',
                'Chrome · Android',
            ],
            'Chrome on iPad (CriOS)' => [
                'Mozilla/5.0 (iPad; CPU OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/126.0.6478.54 Mobile/15E148 Safari/604.1',
                'Chrome · iPad',
            ],
            'Browser recognised, platform not' => [
                'Mozilla/5.0 (Unknown OS) Gecko/20100101 Firefox/127.0',
                'Firefox',
            ],
            'Platform recognised, browser not' => [
                'SomeCustomClient/1.0 (Windows NT 10.0)',
                'Windows',
            ],
        ];
    }

    #[DataProvider('recognisedUserAgentProvider')]
    public function test_it_summarises_a_recognised_user_agent(string $userAgent, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, (new DeriveTrustedDeviceLabelAction)->handle($userAgent));
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function unrecognisedUserAgentProvider(): array
    {
        return [
            'missing header' => [null],
            'empty string' => [''],
            'whitespace only' => ['   '],
            'unknown client' => ['curl/8.5.0'],
        ];
    }

    #[DataProvider('unrecognisedUserAgentProvider')]
    public function test_it_returns_null_when_the_user_agent_is_missing_or_unrecognised(?string $userAgent): void
    {
        // Null, not an English "Unknown device": the generic fallback is
        // translated at display time (settings.security.twoFactor.
        // trustedDevices.unknownDevice), in the viewer's own language.
        $this->assertNull((new DeriveTrustedDeviceLabelAction)->handle($userAgent));
    }
}
