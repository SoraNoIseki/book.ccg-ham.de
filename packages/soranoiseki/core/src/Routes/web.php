<?php

use Illuminate\Support\Facades\Route;
use Soranoiseki\Core\Controllers\FileController;


Route::middleware('web', 'auth')->group(function () {
    Route::get('/uploads/images/{src?}', [FileController::class, 'getUploadImage'])->where('src', '.*')->name('file.getUploadImage');
    Route::get('/uploads/{src?}', [FileController::class, 'getUploadFile'])->where('src', '.*')->name('file.getUploadFile');
   
    Route::post('/file/save', [FileController::class, 'store'])->name('file.saveFile');
});
