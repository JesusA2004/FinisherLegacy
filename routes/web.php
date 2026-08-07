<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegacyCodeController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
