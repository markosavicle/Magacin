<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\TransactionController;


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
    Route::get('partners/{partner}/export-pdf', [PartnerController::class, 'exportPdf'])->name('partners.exportPdf');
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/export-pdf', [InvoiceController::class, 'exportPdf'])->name('invoices.exportPdf');
    Route::resource('transactions', TransactionController::class)->except(['show']);
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
});



require __DIR__.'/auth.php';
require __DIR__.'/user_approval.php';
