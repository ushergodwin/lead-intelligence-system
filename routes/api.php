<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\LogController;
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
        // Static routes MUST be defined before parameterized /{lead} routes
        Route::post('/',                   [LeadController::class, 'store'])->name('store');
        Route::get('/export/csv',          [LeadController::class, 'exportCsv'])->name('export.csv');
        Route::post('/bulk/approve',       [LeadController::class, 'bulkApprove'])->name('bulk.approve');
        Route::post('/bulk/archive',       [LeadController::class, 'bulkArchive'])->name('bulk.archive');
        Route::post('/bulk/delete',        [LeadController::class, 'bulkDelete'])->name('bulk.delete');

        // Parameterized routes
        Route::get('/{lead}',              [LeadController::class, 'show'])->name('show');
        Route::patch('/{lead}',            [LeadController::class, 'update'])->name('update');
        Route::patch('/{lead}/approve',    [LeadController::class, 'approve'])->name('approve');
        Route::patch('/{lead}/archive',    [LeadController::class, 'archive'])->name('archive');
        Route::patch('/{lead}/unarchive',  [LeadController::class, 'unarchive'])->name('unarchive');
        Route::patch('/{lead}/notes',      [LeadController::class, 'updateNotes'])->name('notes.update');
        Route::post('/{lead}/rescore',     [LeadController::class, 'rescore'])->name('rescore');
        Route::post('/{lead}/send-email',  [LeadController::class, 'sendEmail'])->name('send-email');
        Route::post('/{lead}/send-sms',    [LeadController::class, 'sendSms'])->name('send-sms');
        Route::delete('/{lead}',           [LeadController::class, 'destroy'])->name('destroy');
    });

    // Outreach logs
    Route::post('/logs/{log}/retry', [LogController::class, 'retry'])->name('logs.retry');

    // Settings — super_admin only (enforced inside controller)
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // User management — super_admin only
    Route::middleware('role:super_admin')->prefix('users')->name('users.')->group(function () {
        Route::post('/',                     [UserController::class, 'store'])->name('store');
        Route::put('/{user}',                [UserController::class, 'update'])->name('update');
        Route::delete('/{user}',             [UserController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/resend-invite', [UserController::class, 'resendInvite'])->name('resend-invite');
    });
});
