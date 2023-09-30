<?php

use Illuminate\Support\Facades\Route;
use Soranoiseki\BookGroup\Controllers\PowerpointController;
use Soranoiseki\BookGroup\Controllers\LibraryController;


Route::middleware('web', 'auth')->group(function () {
    Route::group(['prefix' => 'ppt'], function() {
        Route::get('/', [PowerpointController::class, 'index'])->name('book-group.ppt.index');
        Route::post('/save', [PowerpointController::class, 'store'])->name('book-group.ppt.store');
        Route::post('/download', [PowerpointController::class, 'download'])->name('book-group.ppt.download');
    });
    
    Route::group(['prefix' => 'library'], function() {
        Route::get('/', [LibraryController::class, 'index'])->name('library.index');

        Route::get('/book/{bookId}/{copyId}/borrow', [LibraryController::class, 'borrowBook'])->name('library.book.borrow');
        Route::get('/book/{bookId}/{copyId}/return', [LibraryController::class, 'returnBook'])->name('library.book.return');
        // Route::get('/book/');

        Route::get('/import', [LibraryController::class, 'importBooks'])->name('library.import');
        Route::post('/import/books', [LibraryController::class, 'importBooks'])->name('library.import.books');
        Route::post('/import/members', [LibraryController::class, 'importMembers'])->name('library.import.members');
       
    });
});
