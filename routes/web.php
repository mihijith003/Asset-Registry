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



// Staff & Admin can access these
Route::middleware(['auth', 'permission:register assets'])->group(function () {
    Route::get('/assets', [AssetController::class, 'index']);
    Route::post('/assets', [AssetController::class, 'store']);
});

Route::middleware(['auth', 'permission:modify assets'])->group(function () {
    Route::get('/asset-modifications', [AssetModificationController::class, 'index']);
    Route::post('/asset-modifications', [AssetModificationController::class, 'store']);
});

// ONLY Admin can access these
Route::middleware(['auth', 'permission:sell assets'])->group(function () {
    Route::get('/asset-sales', [AssetSaleController::class, 'index']);
    Route::post('/asset-sales', [AssetSaleController::class, 'store']);
});

Route::middleware(['auth', 'permission:dispose assets'])->group(function () {
    Route::get('/asset-disposals', [AssetDisposalController::class, 'index']);
    Route::post('/asset-disposals', [AssetDisposalController::class, 'store']);
});

require __DIR__.'/auth.php';
