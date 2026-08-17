<?php

use App\Http\Controllers\AreaRequestController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoverageCheckController;
use App\Http\Controllers\CustomerAssignmentController;
use App\Http\Controllers\CustomerSubscriptionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InternetPackageController;
use App\Http\Controllers\InternalPortalController;
use App\Http\Controllers\InternalUserController;
use App\Http\Controllers\NetworkMarkerController;
use App\Http\Controllers\NetworkProvisioningController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/admin', [InternalPortalController::class, 'admin'])->name('internal.admin');
    Route::get('/teknisi', [InternalPortalController::class, 'teknisi'])->name('internal.teknisi');
    Route::post('/network-markers', [NetworkMarkerController::class, 'store'])->name('network-markers.store');
    Route::delete('/network-markers/{networkMarker}/odp', [NetworkMarkerController::class, 'destroyOdpMarker'])->name('network-markers.odp.destroy');
    Route::delete('/odps/{odp}', [NetworkMarkerController::class, 'destroyOdp'])->name('odps.destroy');
    Route::post('/customers/{customerSubscription}/assign-odp', [CustomerAssignmentController::class, 'assign'])->name('customers.assign-odp');
    Route::post('/internet-packages', [InternetPackageController::class, 'store'])->name('internet-packages.store');
    Route::put('/internet-packages/{internetPackage}', [InternetPackageController::class, 'update'])->name('internet-packages.update');
    Route::delete('/internet-packages/{internetPackage}', [InternetPackageController::class, 'destroy'])->name('internet-packages.destroy');
    Route::post('/network-provisioning', [NetworkProvisioningController::class, 'store'])->name('network-provisioning.store');

    Route::post('/internal-users', [InternalUserController::class, 'store'])->name('internal-users.store');
    Route::put('/internal-users/{user}', [InternalUserController::class, 'update'])->name('internal-users.update');
    Route::patch('/internal-users/{user}/deactivate', [InternalUserController::class, 'deactivate'])->name('internal-users.deactivate');
    Route::delete('/internal-users/{user}', [InternalUserController::class, 'destroy'])->name('internal-users.destroy');
});
Route::post('/coverage/check', CoverageCheckController::class)->name('coverage.check');
Route::post('/area-requests', [AreaRequestController::class, 'store'])->name('area-requests.store');
Route::post('/subscriptions', [CustomerSubscriptionController::class, 'store'])->name('subscriptions.store');
