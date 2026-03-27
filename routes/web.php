<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\InvoiceController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


use App\Http\Controllers\HistoryController;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('partners', PartnerController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
});



require __DIR__.'/auth.php';
