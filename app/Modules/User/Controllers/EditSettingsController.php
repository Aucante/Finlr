<?php

namespace App\Modules\User\Controllers;

use App\Modules\Shared\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EditSettingsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        // The route sits behind the `auth` middleware, so $user is never
        // null in practice; PHPStan still needs this explicit proof to
        // allow accessing non-nullable User properties/methods below.
        abort_if($user === null, 403);

        return Inertia::render('Settings/Edit', [
            // Deliberately `true`, not `$user instanceof MustVerifyEmail`.
            // App\Modules\User\Models\User is the only Authenticatable this
            // app has, and it implements MustVerifyEmail unconditionally at
            // the class level — so once $user is proven non-null above,
            // PHPStan (Larastan resolves $request->user() to the concrete
            // User class, the app's single auth-guard model) knows the
            // instanceof is always true and fails the build with
            // instanceof.alwaysTrue. Verified by actually restoring the
            // instanceof and running `phpstan analyse` (level 5, the
            // project's own level — not just a stricter one): it fails,
            // every time, as long as User keeps that unconditional
            // `implements`. The only ways to keep the instanceof check
            // would be an inline error-suppression comment (PHPStan's own
            // error output explicitly says not to add one) or relaxing
            // `treatPhpDocTypesAsCertain` project-wide in phpstan.neon
            // (weakens this check everywhere, for one call site) — neither
            // is worth it for a condition that cannot currently be false.
            // If a second Authenticatable without MustVerifyEmail is ever
            // introduced (an admin guard, impersonation), reinstate the
            // instanceof check then — it will type-check cleanly at that
            // point, because it will no longer be provably always true.
            'mustVerifyEmail' => true,
            'status' => session('status'),
            'memberSince' => $user->created_at?->toISOString(),
            'profileUpdatedAt' => $user->updated_at?->toISOString(),
            'scenariosCount' => $user->scenarios()->count(),
        ]);
    }
}
