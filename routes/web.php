<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetDisposalController;
use App\Http\Controllers\AssetModificationController;
use App\Http\Controllers\AssetSaleController;
use App\Http\Controllers\ProfileController;
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
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Profile management
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Asset Management Routes protected by role middleware (Admin and Staff)
Route::middleware(['auth', 'role:Admin|Staff'])->group(function () {
    // Asset Registry
    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
    Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');

    // Asset Modification
    Route::get('/asset-modifications', [AssetModificationController::class, 'index'])->name('asset-modifications.index');
    Route::post('/asset-modifications', [AssetModificationController::class, 'store'])->name('asset-modifications.store');

    // Asset Sale
    Route::get('/asset-sales', [AssetSaleController::class, 'index'])->name('asset-sales.index');
    Route::post('/asset-sales', [AssetSaleController::class, 'store'])->name('asset-sales.store');

    // Asset Disposal
    Route::get('/asset-disposals', [AssetDisposalController::class, 'index'])->name('asset-disposals.index');
    Route::post('/asset-disposals', [AssetDisposalController::class, 'store'])->name('asset-disposals.store');
});

require __DIR__.'/auth.php';
