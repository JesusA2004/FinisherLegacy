<?php

use App\Http\Controllers\Admin\AthleteController as AdminAthleteController;
use App\Http\Controllers\Admin\AthleteIdentityConflictController as AdminAthleteIdentityConflictController;
use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EditionController as AdminEditionController;
use App\Http\Controllers\Admin\IncidentController as AdminIncidentController;
use App\Http\Controllers\Admin\LegacyCodeController as AdminLegacyCodeController;
use App\Http\Controllers\Admin\MachineProfileController as AdminMachineProfileController;
use App\Http\Controllers\Admin\OrganizerController as AdminOrganizerController;
use App\Http\Controllers\Admin\ParticipantController as AdminParticipantController;
use App\Http\Controllers\Admin\PlateController as AdminPlateController;
use App\Http\Controllers\Admin\PlateStudioController as AdminPlateStudioController;
use App\Http\Controllers\Admin\PreregistrationController as AdminPreregistrationController;
use App\Http\Controllers\Admin\ProductionDeviceController as AdminProductionDeviceController;
use App\Http\Controllers\Admin\ProductionSetupController as AdminProductionSetupController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
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
        Route::post('preview', [OperatorController::class, 'previewPlate'])->middleware('throttle:60,1')->name('preview');
        Route::get('participants/{eventParticipant}', [OperatorController::class, 'showParticipant'])->name('participants.show');
        Route::post('participants/{eventParticipant}/plate', [OperatorController::class, 'generateIntegratedPlate'])->name('participants.plate');
        Route::post('quick-plate', [OperatorController::class, 'generateQuickPlate'])->name('quick-plate');
        Route::get('quick-plate/{plate}', [OperatorController::class, 'showQuickPlate'])->name('quick-plate.show');
    });

    Route::middleware('can:production.access')->prefix('production')->name('production.')->group(function () {
        Route::get('/', [ProductionController::class, 'index'])->name('index');

        // Manual/web fallback for the same physical workflow the Device
        // API drives (docs/adr/0003-production-state-machine.md §45) —
        // every route here calls the same Actions as the Device API.
        Route::middleware('can:production.manage')->prefix('jobs/{job}')->name('jobs.')->group(function () {
            Route::patch('prepare', [ProductionController::class, 'prepare'])->name('prepare');
            Route::patch('front/start', [ProductionController::class, 'frontStart'])->name('front.start');
            Route::patch('front/complete', [ProductionController::class, 'frontComplete'])->name('front.complete');
            Route::patch('flip/confirm', [ProductionController::class, 'flipConfirm'])->name('flip.confirm');
            Route::patch('back/start', [ProductionController::class, 'backStart'])->name('back.start');
            Route::patch('back/complete', [ProductionController::class, 'backComplete'])->name('back.complete');
            Route::post('qr/verify', [ProductionController::class, 'qrVerify'])->name('qr.verify');
            Route::patch('deliver', [ProductionController::class, 'deliver'])->name('deliver');
            Route::post('fail', [ProductionController::class, 'fail'])->name('fail');
            Route::patch('cancel', [ProductionController::class, 'cancel'])->name('cancel');
        });
    });

    // Production file downloads: gated by plates.view alone (not the wider
    // dashboard.admin.view admin-panel gate) so operators and production staff can
    // download what they just generated without needing full admin access.
    Route::middleware('can:plates.view')->prefix('admin/plates')->name('admin.plates.')->group(function () {
        Route::get('{plate}/export/{face}/{format}', [AdminPlateController::class, 'export'])->name('export');
        Route::get('{plate}/export-qr/{format?}', [AdminPlateController::class, 'exportQr'])->name('export-qr');
        Route::get('{plate}/export-package', [AdminPlateController::class, 'exportPackage'])->name('export-package');
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

        Route::middleware('can:editions.manage')->prefix('events/{eventEdition}/production-setup')->name('editions.production-setup.')->group(function () {
            Route::get('/', [AdminProductionSetupController::class, 'show'])->name('show');
            Route::post('assign-template', [AdminProductionSetupController::class, 'assignTemplate'])->name('assign-template');
            Route::post('qr-test', [AdminProductionSetupController::class, 'markQrTested'])->name('qr-test');
        });
        Route::middleware('can:preregistrations.view')->get('preregistrations', [AdminPreregistrationController::class, 'index'])->name('preregistrations.index');
        Route::middleware('can:participants.view')->get('participants', [AdminParticipantController::class, 'index'])->name('participants.index');

        // Canonical athlete identity (docs/adr/0004-athlete-canonical-identity.md).
        Route::middleware('can:athletes.view')->prefix('athletes')->name('athletes.')->group(function () {
            Route::get('/', [AdminAthleteController::class, 'index'])->name('index');
            Route::get('{athlete}', [AdminAthleteController::class, 'show'])->name('show');
        });
        Route::middleware('can:athletes.manage')->prefix('identity-conflicts')->name('identity-conflicts.')->group(function () {
            Route::get('/', [AdminAthleteIdentityConflictController::class, 'index'])->name('index');
            Route::post('{conflict}/resolve', [AdminAthleteIdentityConflictController::class, 'resolve'])->name('resolve');
        });

        Route::middleware('can:plates.view')->group(function () {
            Route::get('plates', [AdminPlateController::class, 'index'])->name('plates.index');
            Route::post('plates/export-batch', [AdminPlateController::class, 'exportBatch'])->name('plates.export-batch');
            Route::get('plates/{plate}', [AdminPlateController::class, 'show'])->name('plates.show');
            Route::middleware('can:plates.manage')->post('plates/{plate}/reprint', [AdminPlateController::class, 'reprint'])->name('plates.reprint');
        });
        Route::middleware('can:legacycodes.view')->get('legacy-codes', [AdminLegacyCodeController::class, 'index'])->name('legacy-codes.index');

        Route::middleware('can:platetemplates.view')->prefix('plate-studio')->name('plate-studio.')->group(function () {
            Route::get('/', [AdminPlateStudioController::class, 'index'])->name('index');
            Route::get('templates/{plateTemplate}/versions/{plateTemplateVersion}', [AdminPlateStudioController::class, 'edit'])->name('edit');
            Route::post('preview', [AdminPlateStudioController::class, 'preview'])->name('preview');
            Route::get('versions/{plateTemplateVersion}/test-export/{face}', [AdminPlateStudioController::class, 'testExport'])->name('versions.test-export');
            Route::get('calibration/{face}', [AdminPlateStudioController::class, 'calibrationCard'])->name('calibration');

            Route::middleware('can:platetemplates.manage')->group(function () {
                Route::post('templates', [AdminPlateStudioController::class, 'store'])->name('templates.store');
                Route::patch('templates/{plateTemplate}', [AdminPlateStudioController::class, 'update'])->name('templates.update');
                Route::post('templates/{plateTemplate}/duplicate', [AdminPlateStudioController::class, 'duplicate'])->name('templates.duplicate');
                Route::post('templates/{plateTemplate}/archive', [AdminPlateStudioController::class, 'archiveTemplate'])->name('templates.archive');
                Route::post('templates/{plateTemplate}/versions', [AdminPlateStudioController::class, 'createVersion'])->name('versions.create');
                Route::patch('versions/{plateTemplateVersion}', [AdminPlateStudioController::class, 'updateVersion'])->name('versions.update');
                Route::post('versions/{plateTemplateVersion}/publish', [AdminPlateStudioController::class, 'publish'])->name('versions.publish');
                Route::post('versions/{plateTemplateVersion}/archive', [AdminPlateStudioController::class, 'archiveVersion'])->name('versions.archive');
            });
        });

        // Machine profiles: a workflow label ("Fiber 30W — LightBurn"), not
        // a driver — gated the same as plate templates since it's part of
        // the same production-configuration surface.
        Route::middleware('can:platetemplates.view')->prefix('machine-profiles')->name('machine-profiles.')->group(function () {
            Route::get('/', [AdminMachineProfileController::class, 'index'])->name('index');

            Route::middleware('can:platetemplates.manage')->group(function () {
                Route::post('/', [AdminMachineProfileController::class, 'store'])->name('store');
                Route::patch('{machineProfile}', [AdminMachineProfileController::class, 'update'])->name('update');
            });
        });

        // Estaciones: Super Admin only in practice — `admin` gets every
        // permission via RolePermissionSeeder, `super_admin` bypasses via
        // Gate::before, and no other seeded role is granted these keys.
        Route::middleware('can:productiondevices.view')->prefix('production-devices')->name('production-devices.')->group(function () {
            Route::get('/', [AdminProductionDeviceController::class, 'index'])->name('index');

            Route::middleware('can:productiondevices.manage')->group(function () {
                Route::post('pairings/{pairingRequest}/approve', [AdminProductionDeviceController::class, 'approvePairing'])->name('pairings.approve');
                Route::post('{device}/revoke', [AdminProductionDeviceController::class, 'revoke'])->name('revoke');
            });
        });

        Route::middleware('can:incidents.view')->get('incidents', [AdminIncidentController::class, 'index'])->name('incidents.index');
        Route::middleware('can:incidents.manage')->patch('incidents/{incident}/resolve', [AdminIncidentController::class, 'resolve'])->name('incidents.resolve');

        Route::middleware('can:users.view')->get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::middleware('can:users.manage')->group(function () {
            Route::get('users/create', [AdminUserController::class, 'create'])->name('users.create');
            Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
            Route::patch('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
            Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.update-status');
            Route::patch('users/{user}/roles', [AdminUserController::class, 'updateRoles'])->name('users.update-roles');
            Route::post('users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
        });

        Route::middleware('can:roles.manage')->prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [AdminRoleController::class, 'index'])->name('index');
            Route::get('create', [AdminRoleController::class, 'create'])->name('create');
            Route::post('/', [AdminRoleController::class, 'store'])->name('store');
            Route::get('{role}/edit', [AdminRoleController::class, 'edit'])->name('edit');
            Route::patch('{role}', [AdminRoleController::class, 'update'])->name('update');
            Route::post('{role}/duplicate', [AdminRoleController::class, 'duplicate'])->name('duplicate');
        });

        Route::middleware('can:organizers.view')->get('organizers', [AdminOrganizerController::class, 'index'])->name('organizers.index');
        Route::middleware('can:organizers.manage')->post('organizers', [AdminOrganizerController::class, 'store'])->name('organizers.store');
        Route::middleware('can:organizers.manage')->patch('organizers/{organizer}', [AdminOrganizerController::class, 'update'])->name('organizers.update');

        Route::middleware('can:audit.view')->get('audit', [AdminAuditController::class, 'index'])->name('audit.index');
    });
});

require __DIR__.'/settings.php';
