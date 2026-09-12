<?php

use App\Modules\Auth\Exceptions\PendingTwoFactorSessionExpiredException;
use App\Modules\Shared\Middleware\HandleInertiaRequests;
use App\Modules\SimulationEngine\Exceptions\SimulationEngineUnavailableException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // `shouldRenderJsonWhen` above (scoped to `api/*`) means Laravel's
        // default unauthenticated() handling no longer recognises an
        // Inertia PUT/PATCH/DELETE request as "expecting JSON": it falls
        // through to a plain `redirect()->guest(route('login'))`, i.e. a
        // 302 with no X-Inertia header. Axios/XHR preserves the original
        // HTTP method across that redirect (unlike `fetch`, which downgrades
        // to GET), so the browser retries e.g. PUT against /login — a route
        // that only accepts GET/POST — surfacing as a confusing
        // MethodNotAllowedHttpException instead of "please log in again".
        // Inertia::location() sends the redirect the way Inertia's client
        // expects (409 + X-Inertia-Location), which it turns into a full
        // page visit to the login page instead of replaying the request.
        $exceptions->render(function (AuthenticationException $exception, Request $request): ?Response {
            if ($request->header('X-Inertia')) {
                return Inertia::location(route('login'));
            }

            return null;
        });

        // Any simulator that lets SimulationEngineUnavailableException
        // bubble up gets this handling for free: the actual failure is
        // already logged (with its original cause preserved) by the time it
        // reaches here, so this only turns it into a redirect with a
        // generic, translated message. The key lives at the top of the
        // `simulator` namespace (not under one simulator's own `form`)
        // precisely because it is shared across all of them.
        $exceptions->render(fn (SimulationEngineUnavailableException $exception): RedirectResponse => back()->withErrors([
            'simulation' => __('simulator.calculationFailed'),
        ]));

        // Closing the tab mid-challenge, or letting the pending window run
        // out (CONCEPTION.md, section 6), must never surface as a raw 500
        // — just send the user back to a normal, unauthenticated login.
        $exceptions->render(fn (PendingTwoFactorSessionExpiredException $exception): RedirectResponse => redirect()->route('login')->withErrors([
            'email' => __('auth.two_factor.challenge.session_expired'),
        ]));
    })->create();
