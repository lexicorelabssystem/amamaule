<?php

use App\Http\Controllers\PublicCatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('catalog')->name('api.catalog.')->group(function () {
    Route::get('artists', [PublicCatalogController::class, 'artists'])->name('artists');
    Route::get('activities', [PublicCatalogController::class, 'activities'])->name('activities');
    Route::get('disciplines', [PublicCatalogController::class, 'disciplines'])->name('disciplines');
    Route::get('territories', [PublicCatalogController::class, 'territories'])->name('territories');
});
