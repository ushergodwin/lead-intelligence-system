<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All data-fetching and data-mutation endpoints live here.
| Authenticated via Laravel Sanctum (cookie for SPA, Bearer token for mobile).
|
*/

// ---- Public: Mobile / external token auth ----
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('/login',  [AuthController::class, 'login'])->name('login');
});

// ---- Authenticated endpoints ----
Route::middleware('auth:sanctum')->group(function () {

    // Auth / Token management
    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::post('/logout',              [AuthController::class, 'logout'])->name('logout');
        Route::get('/me',                   [AuthController::class, 'me'])->name('me');
        Route::get('/tokens',               [AuthController::class, 'tokens'])->name('tokens.index');
        Route::post('/tokens',              [AuthController::class, 'createToken'])->name('tokens.store');
        Route::delete('/tokens/{tokenId}',  [AuthController::class, 'revokeToken'])->name('tokens.destroy');
    });

    // Leads — JSON actions (all roles can read; manager+ to act)
    Route::prefix('leads')->name('leads.')->group(function () {
        Route::get('/{lead}',              [LeadController::class, 'show'])->name('show');
        Route::patch('/{lead}/approve',    [LeadController::class, 'approve'])->name('approve');
        Route::post('/{lead}/send-email',  [LeadController::class, 'sendEmail'])->name('send-email');
        Route::post('/{lead}/send-sms',    [LeadController::class, 'sendSms'])->name('send-sms');
        Route::delete('/{lead}',           [LeadController::class, 'destroy'])->name('destroy');
    });

    // Settings — super_admin only (enforced inside controller)
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // User management — super_admin only
    Route::middleware('role:super_admin')->prefix('users')->name('users.')->group(function () {
        Route::post('/',         [UserController::class, 'store'])->name('store');
        Route::put('/{user}',    [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });
});
