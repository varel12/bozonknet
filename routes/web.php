<?php

use App\Http\Controllers\AreaRequestController;
use App\Http\Controllers\CoverageCheckController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/coverage/check', CoverageCheckController::class)->name('coverage.check');
Route::post('/area-requests', [AreaRequestController::class, 'store'])->name('area-requests.store');
