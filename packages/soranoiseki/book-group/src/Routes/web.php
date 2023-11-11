<?php

use Illuminate\Support\Facades\Route;
use Soranoiseki\BookGroup\Controllers\PowerpointController;
use Soranoiseki\BookGroup\Controllers\LibraryController;
use Soranoiseki\BookGroup\Controllers\CalendarController;


Route::middleware('web', 'auth')->group(function () {
    Route::group(['prefix' => 'ppt'], function() {
        Route::get('/', [PowerpointController::class, 'index'])->name('book-group.ppt.index');
        Route::post('/save', [PowerpointController::class, 'store'])->name('book-group.ppt.store');
        Route::post('/download', [PowerpointController::class, 'download'])->name('book-group.ppt.download');
        Route::post('/save-download', [PowerpointController::class, 'storeAndDownload'])->name('book-group.ppt.store-download');
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

    Route::group(['prefix' => 'calendar'], function() {
        Route::get('/', [CalendarController::class, 'index'])->name('book-group.calendar.index');
        Route::get('/{calendar}', [CalendarController::class, 'show'])->where('calendar', '[0-9]+')->name('book-group.calendar.show');
        Route::put('/{calendar}', [CalendarController::class, 'update'])->where('calendar', '[0-9]+')->name('book-group.calendar.update');
        Route::delete('/{calendar}', [CalendarController::class, 'delete'])->where('calendar', '[0-9]+')->name('book-group.calendar.delete');
        Route::post('/create', [CalendarController::class, 'create'])->name('book-group.calendar.create');
        Route::get('/versions', [CalendarController::class, 'getVersions'])->name('book-group.calendar.versions');
        Route::get('/pdf/{calendar}', [CalendarController::class, 'generate'])->where('calendar', '[0-9]+')->name('book-group.calendar.generate');
        Route::get('/preview/{calendar}', [CalendarController::class, 'generate'])->where('calendar', '[0-9]+')->name('book-group.calendar.preview');
        Route::post('/import/events/{calendar}', [CalendarController::class, 'importEvents'])->where('calendar', '[0-9]+')->name('book-group.calendar.import-events');
        Route::post('/import/bible-texts/{calendar}', [CalendarController::class, 'importBibleTexts'])->where('calendar', '[0-9]+')->name('book-group.calendar.import-bible-texts');
        Route::get('/update-holidays', [CalendarController::class, 'updateHolidays'])->name('book-group.calendar.update-holidays');
    });
    
});
