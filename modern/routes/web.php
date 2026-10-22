<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerSearchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('tickets', TicketController::class);
    Route::patch('tickets/{ticket}/status', TicketStatusController::class)->name('tickets.status');
    Route::post('tickets/{ticket}/comments', CommentController::class)->name('tickets.comments.store');

    Route::get('customers/search', CustomerSearchController::class)->name('customers.search');
    Route::resource('customers', CustomerController::class)->except('show');
});
