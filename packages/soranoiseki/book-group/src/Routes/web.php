<?php

use Illuminate\Support\Facades\Route;
use Soranoiseki\BookGroup\Controllers\PowerpointController;


Route::middleware('web', 'auth')->group(function () {
    Route::group(['prefix' => 'ppt'], function() {
        Route::get('/', [PowerpointController::class, 'index'])->name('book-group.ppt.index');
        Route::post('/save', [PowerpointController::class, 'store'])->name('book-group.ppt.store');
        Route::post('/download', [PowerpointController::class, 'download'])->name('book-group.ppt.download');
        Route::post('/save-download', [PowerpointController::class, 'storeAndDownload'])->name('book-group.ppt.store-download');
    });
});
