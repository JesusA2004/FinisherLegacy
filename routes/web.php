<?php

use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EditionController as AdminEditionController;
use App\Http\Controllers\Admin\IncidentController as AdminIncidentController;
use App\Http\Controllers\Admin\LegacyCodeController as AdminLegacyCodeController;
use App\Http\Controllers\Admin\OrganizerController as AdminOrganizerController;
use App\Http\Controllers\Admin\ParticipantController as AdminParticipantController;
use App\Http\Controllers\Admin\PlateController as AdminPlateController;
use App\Http\Controllers\Admin\PreregistrationController as AdminPreregistrationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AthleteProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LegacyCodeController;
use App\Http\Controllers\MedalController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\PreregistrationController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\PublicProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::inertia('how-it-works', 'HowItWorks')->name('how-it-works');
Route::inertia('privacy', 'Privacy')->name('privacy');
Route::inertia('terms', 'Terms')->name('terms');
Route::inertia('contact', 'Contact')->name('contact');

Route::get('events', [EventController::class, 'index'])->name('events.index');
Route::get('events/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::get('events/{event:slug}/preregister', [EventController::class, 'preregister'])->name('events.preregister');
Route::post('events/{event:slug}/preregister', [EventController::class, 'storePreregistration'])
    ->middleware('throttle:api-register')
    ->name('preregistrations.store');

Route::get('preregistrations/{token}', [PreregistrationController::class, 'show'])->name('preregistrations.show');
Route::get('preregistrations/{token}/qr.svg', [PreregistrationController::class, 'qr'])->name('preregistrations.qr');

Route::get('l/{code}', [LegacyCodeController::class, 'show'])->name('legacy-code.show');
Route::get('l/{code}/qr.svg', [LegacyCodeController::class, 'qr'])->name('legacy-code.qr');
Route::get('l/{code}/continue/{provider}', [LegacyCodeController::class, 'continueTo'])->name('legacy-code.continue');

Route::get('/@{athleteProfile:username}', [PublicProfileController::class, 'show'])->name('profile.public');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('l/{code}/claim', [LegacyCodeController::class, 'claim'])->name('legacy-code.claim');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('dashboard/profile/edit', [AthleteProfileController::class, 'edit'])->name('dashboard.profile.edit');
    Route::patch('dashboard/profile', [AthleteProfileController::class, 'update'])->name('dashboard.profile.update');

    Route::get('dashboard/medals/search/events', [MedalController::class, 'searchEvents'])->name('dashboard.medals.search-events');
    Route::get('dashboard/medals/search/match', [MedalController::class, 'matchParticipant'])->name('dashboard.medals.match-participant');

    Route::resource('dashboard/medals', MedalController::class)
        ->parameters(['medals' => 'medal'])
        ->names('dashboard.medals');

    Route::delete('dashboard/medals/{medal}/gallery/{medalImage}', [MedalController::class, 'destroyGalleryImage'])
        ->name('dashboard.medals.gallery.destroy');

    Route::middleware('can:operator.access')->prefix('operator')->name('operator.')->group(function () {
        Route::get('/', [OperatorController::class, 'index'])->name('index');
        Route::post('event', [OperatorController::class, 'selectEvent'])->name('select-event');
        Route::get('search', [OperatorController::class, 'search'])->middleware('throttle:60,1')->name('search');
        Route::get('participants/{eventParticipant}', [OperatorController::class, 'showParticipant'])->name('participants.show');
        Route::post('participants/{eventParticipant}/plate', [OperatorController::class, 'generateIntegratedPlate'])->name('participants.plate');
        Route::post('quick-plate', [OperatorController::class, 'generateQuickPlate'])->name('quick-plate');
    });

    Route::middleware('can:production.access')->prefix('production')->name('production.')->group(function () {
        Route::get('/', [ProductionController::class, 'index'])->name('index');
        Route::patch('plates/{plate}/status', [ProductionController::class, 'updateStatus'])
            ->middleware('can:production.manage')
            ->name('plates.status');
    });

    Route::middleware('can:imports.manage')->prefix('imports')->name('imports.')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::get('create', [ImportController::class, 'create'])->name('create');
        Route::post('upload', [ImportController::class, 'upload'])->middleware('throttle:20,1')->name('upload');
        Route::post('/', [ImportController::class, 'store'])->name('store');
        Route::get('{eventImport}', [ImportController::class, 'show'])->name('show');
    });

    Route::middleware('can:dashboard.admin.view')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::middleware('can:events.view')->get('editions', [AdminEditionController::class, 'index'])->name('editions.index');
        Route::middleware('can:preregistrations.view')->get('preregistrations', [AdminPreregistrationController::class, 'index'])->name('preregistrations.index');
        Route::middleware('can:participants.view')->get('participants', [AdminParticipantController::class, 'index'])->name('participants.index');
        Route::middleware('can:plates.view')->get('plates', [AdminPlateController::class, 'index'])->name('plates.index');
        Route::middleware('can:legacycodes.view')->get('legacy-codes', [AdminLegacyCodeController::class, 'index'])->name('legacy-codes.index');

        Route::middleware('can:incidents.view')->get('incidents', [AdminIncidentController::class, 'index'])->name('incidents.index');
        Route::middleware('can:incidents.manage')->patch('incidents/{incident}/resolve', [AdminIncidentController::class, 'resolve'])->name('incidents.resolve');

        Route::middleware('can:users.view')->get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::middleware('can:users.manage')->patch('users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.update-status');
        Route::middleware('can:users.manage')->patch('users/{user}/roles', [AdminUserController::class, 'updateRoles'])->name('users.update-roles');

        Route::middleware('can:organizers.view')->get('organizers', [AdminOrganizerController::class, 'index'])->name('organizers.index');
        Route::middleware('can:organizers.manage')->post('organizers', [AdminOrganizerController::class, 'store'])->name('organizers.store');
        Route::middleware('can:organizers.manage')->patch('organizers/{organizer}', [AdminOrganizerController::class, 'update'])->name('organizers.update');

        Route::middleware('can:audit.view')->get('audit', [AdminAuditController::class, 'index'])->name('audit.index');
    });
});

require __DIR__.'/settings.php';
