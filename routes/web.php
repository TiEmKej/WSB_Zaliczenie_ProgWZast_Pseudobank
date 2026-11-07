<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\TransferController;

Route::redirect('/', '/dashboard');

if (! app()->isProduction()) {
    Route::get('/dev/pesel', [DevController::class, 'pesel'])->name('dev.pesel');
}

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/email/verify', [AuthController::class, 'verifyNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');

    Route::middleware('client')->group(function () {
        Route::get('/recipients', [RecipientController::class, 'index'])->name('recipients.index');
        Route::post('/recipients', [RecipientController::class, 'store'])->name('recipients.store');
        Route::delete('/recipients/{recipient}', [RecipientController::class, 'destroy'])->name('recipients.destroy');
        Route::post('/transfers/{transfer}/repeat', [TransferController::class, 'repeat'])->name('transfers.repeat');
        Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
        Route::get('/transfers/create', [TransferController::class, 'create'])->name('transfers.create');
        Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
        Route::get('/transfers/confirm', [TransferController::class, 'confirm'])->name('transfers.confirm');
        Route::post('/transfers/confirm', [TransferController::class, 'execute'])->name('transfers.execute');
    });

    Route::middleware('admin')->group(function () {
        Route::resource('admin/users', UserController::class)->names('admin.users');
    });
});
