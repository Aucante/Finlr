<?php

namespace App\Modules\Auth\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DestroySessionAction
{
    public function handle(Request $request): void
    {
        // Deliberately does not touch the `two_factor_trusted` cookie: it
        // must survive logout for the 30-day trust window (CONCEPTION.md,
        // section 4, "Survie du cookie de confiance au logout") to mean
        // anything in practice — users log out/in far more often than
        // every 30 days. Never add
        // Cookie::forget(ResolveTrustedDeviceAction::COOKIE_NAME) here.
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
    }
}
