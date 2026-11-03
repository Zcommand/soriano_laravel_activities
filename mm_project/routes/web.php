<?php

use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\MemberController;
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
    Route::patch('/members/{member}/activate', [MemberController::class, 'activate'])->name('members.activate');
    Route::resource('members', MemberController::class);
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('levels', LevelController::class)->except('show');
});
