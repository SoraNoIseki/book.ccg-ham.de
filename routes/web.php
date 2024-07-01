<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin', function () {
            return view('admin.index');
        })->name('admin.index');
        Route::get('/api/admin/users', [UserController::class, 'index']);
        Route::get('/api/admin/roles', [RoleController::class, 'index']);
        Route::get('/api/admin/users/{user}/roles/{role}', [UserController::class, 'toggleUserRole']);
    });
});

require __DIR__.'/auth.php';
