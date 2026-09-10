<?php

use App\Modules\AnalogySimulator\Controllers\RunAnalogyComparisonController;
use App\Modules\AnalogySimulator\Controllers\ShowAnalogySimulatorController;
use App\Modules\Auth\Controllers\ConfirmTwoFactorActivationController;
use App\Modules\Auth\Controllers\DisableTwoFactorController;
use App\Modules\Auth\Controllers\ForgetTrustedDevicesController;
use App\Modules\Auth\Controllers\RequestTwoFactorActivationController;
use App\Modules\FireSimulator\Controllers\RunFireProjectionController;
use App\Modules\FireSimulator\Controllers\ShowFireSimulatorController;
use App\Modules\FreemiumCalculator\Controllers\ShowFreemiumCalculatorController;
use App\Modules\MultiEnvelopeSimulator\Controllers\RunMultiEnvelopeSimulationController;
use App\Modules\MultiEnvelopeSimulator\Controllers\ShowMultiEnvelopeSimulatorController;
use App\Modules\Scenarios\Controllers\RenameScenarioController;
use App\Modules\Scenarios\Controllers\ShowScenarioController;
use App\Modules\Shared\Controllers\ShowDashboardController;
use App\Modules\Shared\Controllers\ShowSimulatorsController;
use App\Modules\SingleEnvelopeSimulator\Controllers\RunSingleEnvelopeSimulationController;
use App\Modules\SingleEnvelopeSimulator\Controllers\ShowSingleEnvelopeSimulatorController;
use App\Modules\SingleEnvelopeSimulator\Controllers\ShowWrapperChoiceController;
use App\Modules\User\Controllers\DeleteAccountController;
use App\Modules\User\Controllers\EditSettingsController;
use App\Modules\User\Controllers\UpdateProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', ShowFreemiumCalculatorController::class)->name('calculator.freemium');

Route::get('/dashboard', ShowDashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/settings', EditSettingsController::class)->name('settings.edit');
    Route::patch('/settings', UpdateProfileController::class)->name('settings.update');
    Route::delete('/settings', DeleteAccountController::class)->name('settings.destroy');

    Route::post('/settings/two-factor', RequestTwoFactorActivationController::class)
        ->middleware('throttle:two-factor-enable')
        ->name('two-factor.enable');

    Route::post('/settings/two-factor/confirm', ConfirmTwoFactorActivationController::class)
        ->middleware('throttle:two-factor-confirm')
        ->name('two-factor.confirm');

    Route::delete('/settings/two-factor', DisableTwoFactorController::class)->name('two-factor.disable');

    Route::delete('/settings/two-factor/trusted-devices', ForgetTrustedDevicesController::class)
        ->name('two-factor.trusted-devices.forget');

    Route::get('/scenarios/{scenario}', ShowScenarioController::class)->name('scenarios.show');
    Route::patch('/scenarios/{scenario}', RenameScenarioController::class)->name('scenarios.rename');
});

// Entry point: pick a simulator type before reaching a specific choice flow.
Route::get('/simulators', ShowSimulatorsController::class)
    ->middleware(['auth', 'verified'])
    ->name('simulators.index');

// Entry point: pick a wrapper before reaching the parameterised form.
Route::get('/simulators/single-envelope', ShowWrapperChoiceController::class)
    ->middleware(['auth', 'verified', 'can:advanced_calculator'])
    ->name('simulators.single-envelope.choose');

// {jurisdiction} and {wrapper} resolve through Laravel's implicit enum
// binding, which 404s on values that aren't cases of the enum. The
// controllers additionally 404 on a wrapper the jurisdiction doesn't offer.
Route::get('/simulators/single-envelope/{jurisdiction}/{wrapper}', ShowSingleEnvelopeSimulatorController::class)
    ->middleware(['auth', 'verified', 'can:advanced_calculator'])
    ->name('simulators.single-envelope.show');

Route::post('/simulators/single-envelope/{jurisdiction}/{wrapper}', RunSingleEnvelopeSimulationController::class)
    ->middleware(['auth', 'verified', 'can:advanced_calculator', 'throttle:run-simulation'])
    ->name('simulators.single-envelope.run');

Route::get('/simulators/multi-envelope', ShowMultiEnvelopeSimulatorController::class)
    ->middleware(['auth', 'verified', 'can:advanced_calculator'])
    ->name('simulators.multi-envelope.show');

Route::post('/simulators/multi-envelope', RunMultiEnvelopeSimulationController::class)
    ->middleware(['auth', 'verified', 'can:advanced_calculator', 'throttle:run-simulation'])
    ->name('simulators.multi-envelope.run');

Route::get('/simulators/analogy', ShowAnalogySimulatorController::class)
    ->middleware(['auth', 'verified', 'can:advanced_calculator'])
    ->name('simulators.analogy.show');

Route::post('/simulators/analogy', RunAnalogyComparisonController::class)
    ->middleware(['auth', 'verified', 'can:advanced_calculator', 'throttle:run-simulation'])
    ->name('simulators.analogy.run');

Route::get('/simulators/fire', ShowFireSimulatorController::class)
    ->middleware(['auth', 'verified', 'can:advanced_calculator'])
    ->name('simulators.fire.show');

Route::post('/simulators/fire', RunFireProjectionController::class)
    ->middleware(['auth', 'verified', 'can:advanced_calculator', 'throttle:run-simulation'])
    ->name('simulators.fire.run');

require __DIR__.'/auth.php';
