<?php

use App\Http\Controllers\AthleteProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegacyCodeController;
use App\Http\Controllers\MedalController;
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

Route::get('l/{code}', [LegacyCodeController::class, 'show'])->name('legacy-code.show');

Route::get('/@{athleteProfile:username}', [PublicProfileController::class, 'show'])->name('profile.public');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('dashboard/profile/edit', [AthleteProfileController::class, 'edit'])->name('dashboard.profile.edit');
    Route::patch('dashboard/profile', [AthleteProfileController::class, 'update'])->name('dashboard.profile.update');

    Route::get('dashboard/medals/search/events', [MedalController::class, 'searchEvents'])->name('dashboard.medals.search-events');
    Route::get('dashboard/medals/search/match', [MedalController::class, 'matchParticipant'])->name('dashboard.medals.match-participant');

    Route::resource('dashboard/medals', MedalController::class)
        ->parameters(['medals' => 'medal'])
        ->names('dashboard.medals');
});

require __DIR__.'/settings.php';
