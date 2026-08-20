<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Devices\DeviceController;
use App\Http\Controllers\Api\V1\Devices\PairingController;
use App\Http\Controllers\Api\V1\Devices\ProductionJobController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\LegacyCodeController;
use App\Http\Controllers\Api\V1\MedalController;
use App\Http\Controllers\Api\V1\PreregistrationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PublicAthleteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — the contract iOS/Android/other Finisher Legacy sites build on.
|--------------------------------------------------------------------------
|
| Every controller here reuses the same Services/Actions/Policies/Form
| Requests as the web Inertia controllers. Never put business logic
| directly in a controller in this file.
*/
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:api-register')
        ->name('auth.register');

    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('auth.login');

    Route::middleware(['auth:sanctum', 'user.token'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('medals', [MedalController::class, 'index'])->name('medals.index');
        Route::post('medals', [MedalController::class, 'store'])->name('medals.store');
        Route::get('medals/{medal:uuid}', [MedalController::class, 'show'])->name('medals.show');
        Route::patch('medals/{medal:uuid}', [MedalController::class, 'update'])->name('medals.update');
        Route::delete('medals/{medal:uuid}', [MedalController::class, 'destroy'])->name('medals.destroy');

        Route::post('legacy-codes/{code}/claim', [LegacyCodeController::class, 'claim'])
            ->middleware('throttle:api-claim')
            ->name('legacy-codes.claim');
    });

    Route::get('athletes/{athleteProfile:username}', [PublicAthleteController::class, 'show'])
        ->name('athletes.show');

    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/{event:slug}', [EventController::class, 'show'])->name('events.show');

    Route::post('events/{edition}/preregister', [PreregistrationController::class, 'store'])
        ->middleware('throttle:api-register')
        ->name('events.preregister');
    Route::get('preregistrations/{token}', [PreregistrationController::class, 'show'])
        ->name('preregistrations.show');

    Route::get('legacy-codes/{code}', [LegacyCodeController::class, 'show'])
        ->middleware('throttle:api-legacy-lookup')
        ->name('legacy-codes.show');

    /*
    |----------------------------------------------------------------------
    | Device API (docs/adr/0002, docs/device-api/v1.md) — the contract a
    | Finisher Event Desktop instance builds on. Still /api/v1: no parallel
    | API surface, just a `device`/`devices`/`production` sub-namespace with
    | its own auth (device.token, not auth:sanctum+user.token) and its own
    | error envelope (see bootstrap/app.php).
    |----------------------------------------------------------------------
    */
    Route::prefix('devices')->name('device.pairing.')->group(function () {
        Route::post('pair', [PairingController::class, 'pair'])
            ->middleware('throttle:api-register')
            ->name('pair');
        // Deliberately a looser limiter than api-register: a desktop polls
        // this every few seconds while waiting for a Super Admin, which
        // isn't the kind of one-shot action api-register is tuned for.
        Route::post('pair/confirm', [PairingController::class, 'confirm'])
            ->middleware('throttle:device-pairing-confirm')
            ->name('confirm');
    });

    Route::middleware(['auth:sanctum', 'device.token'])->group(function () {
        Route::get('device', [DeviceController::class, 'show'])
            ->middleware('ability:device:heartbeat')
            ->name('device.show');
        Route::post('device/heartbeat', [DeviceController::class, 'heartbeat'])
            ->middleware('ability:device:heartbeat')
            ->name('device.heartbeat');

        Route::prefix('production/jobs')->name('device.production.jobs.')->group(function () {
            Route::get('next', [ProductionJobController::class, 'next'])
                ->middleware('ability:production:read')
                ->name('next');
            Route::get('{job}', [ProductionJobController::class, 'show'])
                ->middleware('ability:production:read')
                ->name('show');
            Route::post('{job}/claim', [ProductionJobController::class, 'claim'])
                ->middleware(['ability:production:claim', 'device.idempotent'])
                ->name('claim');
            Route::post('{job}/release', [ProductionJobController::class, 'release'])
                ->middleware(['ability:production:claim', 'device.idempotent'])
                ->name('release');
            Route::get('{job}/artifact/{face}', [ProductionJobController::class, 'artifact'])
                ->middleware('ability:production:read')
                ->name('artifact');

            // Slice 2 (docs/adr/0003) — physical workflow. `production:update`
            // gates coordination steps, `production:engrave` gates the four
            // steps that represent actual laser activity — see
            // App\Enums\DeviceAbility.
            Route::post('{job}/prepare', [ProductionJobController::class, 'prepare'])
                ->middleware(['ability:production:update', 'device.idempotent'])
                ->name('prepare');
            Route::post('{job}/front/start', [ProductionJobController::class, 'frontStart'])
                ->middleware(['ability:production:engrave', 'device.idempotent'])
                ->name('front.start');
            Route::post('{job}/front/complete', [ProductionJobController::class, 'frontComplete'])
                ->middleware(['ability:production:engrave', 'device.idempotent'])
                ->name('front.complete');
            Route::post('{job}/flip/confirm', [ProductionJobController::class, 'flipConfirm'])
                ->middleware(['ability:production:update', 'device.idempotent'])
                ->name('flip.confirm');
            Route::post('{job}/back/start', [ProductionJobController::class, 'backStart'])
                ->middleware(['ability:production:engrave', 'device.idempotent'])
                ->name('back.start');
            Route::post('{job}/back/complete', [ProductionJobController::class, 'backComplete'])
                ->middleware(['ability:production:engrave', 'device.idempotent'])
                ->name('back.complete');
            Route::post('{job}/qr/verify', [ProductionJobController::class, 'qrVerify'])
                ->middleware(['ability:production:update', 'device.idempotent'])
                ->name('qr.verify');
            Route::post('{job}/deliver', [ProductionJobController::class, 'deliver'])
                ->middleware(['ability:production:update', 'device.idempotent'])
                ->name('deliver');
            Route::post('{job}/fail', [ProductionJobController::class, 'fail'])
                ->middleware(['ability:production:update', 'device.idempotent'])
                ->name('fail');
            Route::post('{job}/cancel', [ProductionJobController::class, 'cancel'])
                ->middleware(['ability:production:update', 'device.idempotent'])
                ->name('cancel');
        });
    });
});
