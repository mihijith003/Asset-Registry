<?php

use App\Http\Controllers\AssetAllocationController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetDisposalController;
use App\Http\Controllers\AssetModificationController;
use App\Http\Controllers\AssetRevaluationController;
use App\Http\Controllers\AssetSaleController;
use App\Http\Controllers\AssetTimelineController;
use App\Http\Controllers\DepreciationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\AssetTypeController;
use App\Http\Controllers\AssetSubTypeController;
use App\Http\Controllers\MasterFileController;


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
    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
    Route::get('/assets/print', [AssetController::class, 'print'])->name('assets.print');
    Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
    Route::get('/assets/{asset}/timeline', [AssetTimelineController::class, 'show'])->withTrashed()->name('assets.timeline');
    // NEW: allocate an asset to a branch or an employee (same permission as the registry)
    Route::post('/assets/{asset}/allocations', [AssetAllocationController::class, 'store'])->name('assets.allocate');

    // Master files (Asset Types, Sub Types, Brands)
    Route::get('/master-files', [MasterFileController::class, 'index'])->name('master-files.index');
    Route::post('/asset-types', [AssetTypeController::class, 'store'])->name('asset-types.store');
    Route::patch('/asset-types/{id}', [AssetTypeController::class, 'update'])->name('asset-types.update');
    Route::delete('/asset-types/{id}', [AssetTypeController::class, 'destroy'])->name('asset-types.destroy');
    Route::post('/asset-subtypes', [AssetSubTypeController::class, 'store'])->name('asset-subtypes.store');
    Route::patch('/asset-subtypes/{id}', [AssetSubTypeController::class, 'update'])->name('asset-subtypes.update');
    Route::delete('/asset-subtypes/{id}', [AssetSubTypeController::class, 'destroy'])->name('asset-subtypes.destroy');
    Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
    Route::patch('/brands/{id}', [BrandController::class, 'update'])->name('brands.update');
    Route::delete('/brands/{id}', [BrandController::class, 'destroy'])->name('brands.destroy');
});

//Depreciation report + useful-life settings (same permission as the registry)
Route::middleware(['auth', 'permission:register assets'])->group(function () {
    Route::get('/depreciation', [DepreciationController::class, 'index'])->name('depreciation.index');
    Route::post('/depreciation/settings', [DepreciationController::class, 'updateSettings'])->middleware('throttle:10,1')->name('depreciation.settings');
});

Route::middleware(['auth', 'permission:modify assets'])->group(function () {
    Route::get('/asset-modifications', [AssetModificationController::class, 'index'])->name('asset-modifications.index');
    Route::get('/asset-modifications/print', [AssetModificationController::class, 'printSummary'])->name('asset-modifications.print-summary');
    
    // Route::post('/asset-modifications', [AssetModificationController::class, 'store'])->name('asset-modifications.store');
    Route::post('/asset-modifications', [AssetModificationController::class, 'store'])->middleware('throttle:10,1')->name('asset-modifications.store');
});

Route::middleware(['auth', 'permission:modify assets'])->group(function () {
    Route::get('/asset-revaluations', [AssetRevaluationController::class, 'index'])->name('asset-revaluations.index');
    Route::get('/asset-revaluations/print', [AssetRevaluationController::class, 'printSummary'])->name('asset-revaluations.print-summary');
    Route::post('/asset-revaluations', [AssetRevaluationController::class, 'store'])->middleware('throttle:10,1')->name('asset-revaluations.store');
});

// ONLY Admin can access these
Route::middleware(['auth', 'permission:sell assets'])->group(function () {
    Route::get('/asset-sales', [AssetSaleController::class, 'index'])->name('asset-sales.index');
    Route::get('/asset-sales/print', [AssetSaleController::class, 'printSummary'])->name('asset-sales.print-summary');
    
    // Route::post('/asset-sales', [AssetSaleController::class, 'store'])->name('asset-sales.store');
    Route::post('/asset-sales', [AssetSaleController::class, 'store'])->middleware('throttle:10,1')->name('asset-sales.store');

    // NEW: printable sale voucher (same 'sell assets' permission as the rest of the sales pages)
    Route::get('/asset-sales/{sale}/print', [AssetSaleController::class, 'print'])->name('asset-sales.print');
});

Route::middleware(['auth', 'permission:dispose assets'])->group(function () {
    Route::get('/asset-disposals', [AssetDisposalController::class, 'index'])->name('asset-disposals.index');
    Route::get('/asset-disposals/print', [AssetDisposalController::class, 'printSummary'])->name('asset-disposals.print-summary');
    
    // Route::post('/asset-disposals', [AssetDisposalController::class, 'store'])->name('asset-disposals.store');
    Route::post('/asset-disposals', [AssetDisposalController::class, 'store'])->middleware('throttle:10,1')->name('asset-disposals.store');

    // NEW: printable disposal record (same 'dispose assets' permission as the rest of the disposal pages)
    Route::get('/asset-disposals/{disposal}/print', [AssetDisposalController::class, 'print'])->name('asset-disposals.print');

    // NEW: approve a pending lost item claim
    Route::patch('/asset-disposals/{id}/approve', [AssetDisposalController::class, 'approve'])->name('asset-disposals.approve');

    // NEW: reject a pending lost item claim
    Route::delete('/asset-disposals/{id}/reject', [AssetDisposalController::class, 'reject'])->name('asset-disposals.reject');
});

    

require __DIR__.'/auth.php';
