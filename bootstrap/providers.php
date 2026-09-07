<?php

use App\Modules\Auth\Providers\PasswordPolicyServiceProvider;
use App\Modules\Auth\Providers\RateLimitServiceProvider as AuthRateLimitServiceProvider;
use App\Modules\Auth\Providers\RememberMeServiceProvider;
use App\Modules\SimulationEngine\Providers\SimulationEngineServiceProvider;
use App\Modules\SingleEnvelopeSimulator\Providers\RateLimitServiceProvider as SingleEnvelopeSimulatorRateLimitServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    AuthRateLimitServiceProvider::class,
    PasswordPolicyServiceProvider::class,
    RememberMeServiceProvider::class,
    SimulationEngineServiceProvider::class,
    SingleEnvelopeSimulatorRateLimitServiceProvider::class,
];
