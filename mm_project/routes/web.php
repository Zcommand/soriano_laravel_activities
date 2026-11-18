<?php

use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('members.index');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:staff-login')->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

Route::middleware(['auth', 'can:manage-members'])->group(function (): void {
    Route::get('/weather', [WeatherController::class, 'show'])->middleware('throttle:30,1')->name('weather.show');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/members/{member}/history', [TransactionController::class, 'history'])->name('members.history');
    Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/transactions/{transaction}/approve', [TransactionController::class, 'approve'])->name('transactions.approve');
    Route::post('/transactions/{transaction}/reverse-approval', [TransactionController::class, 'reverseApproval'])->name('transactions.reverse-approval');
    Route::post('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])->name('transactions.cancel');
    Route::get('/transactions/{transaction}/payments/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/transactions/{transaction}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{payment}/void', [PaymentController::class, 'void'])->name('payments.void');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::patch('/members/{member}/activate', [MemberController::class, 'activate'])->name('members.activate');
    Route::resource('members', MemberController::class);
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('levels', LevelController::class)->except('show');
});
