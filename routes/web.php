<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — View rendering only
|--------------------------------------------------------------------------
|
| These routes render Inertia pages (HTML responses).
| All data mutations and JSON endpoints live in routes/api.php.
|
*/

// ---- Root redirect ----
Route::get('/', fn() => redirect()->route('dashboard'));

// ---- Authenticated page routes ----
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/leads',     [LeadController::class, 'index'])->name('leads.index');
    Route::get('/logs',      [LogController::class, 'index'])->name('logs.index');
    Route::get('/settings',  [SettingController::class, 'index'])->name('settings.index');

    // Profile — Inertia forms (session-backed redirects, not JSON)
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User Management page (super_admin only)
    Route::middleware('role:super_admin')
        ->get('/users', [UserController::class, 'index'])
        ->name('users.index');
});

require __DIR__ . '/auth.php';
