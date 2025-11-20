<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\PermissionManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes - accessible only by super admin
Route::middleware(['auth', 'role:super admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserManagementController::class);
    Route::resource('roles', RoleManagementController::class);
    Route::resource('permissions', PermissionManagementController::class);
});

require __DIR__.'/auth.php';
