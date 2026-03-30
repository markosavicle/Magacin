<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserApprovalController;

use App\Http\Controllers\UserController;

Route::middleware(['auth'])->group(function () {
    Route::post('/users/{user}/approve', [UserApprovalController::class, 'approve'])->name('users.approve');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});
