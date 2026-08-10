<?php

use App\Http\Controllers\Api\V1\AuthController;
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

    Route::middleware('auth:sanctum')->group(function () {
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
});
