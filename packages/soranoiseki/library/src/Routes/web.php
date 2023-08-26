<?php

use Illuminate\Support\Facades\Route;
use Soranoiseki\Library\Controllers\LibraryController;


Route::middleware('web', 'auth')->group(function () {
    Route::group(['prefix' => 'library'], function() {
        Route::get('/', [LibraryController::class, 'index'])->name('library.index');
        Route::post('/import/books', [LibraryController::class, 'importBooks'])->name('library.import.books');
        Route::get('/book/{bookId}/{copyId}/borrow', [LibraryController::class, 'borrowBook'])->name('library.book.borrow');
        Route::get('/book/{bookId}/{copyId}/return', [LibraryController::class, 'returnBook'])->name('library.book.return');
    });
});
