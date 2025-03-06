<?php

use Illuminate\Support\Facades\Route;
use Soranoiseki\BookGroup\Controllers\PowerpointController;
use Soranoiseki\BookGroup\Controllers\PowerpointAjaxController;
use Soranoiseki\BookGroup\Controllers\LibraryController;
use Soranoiseki\BookGroup\Controllers\CalendarController;
use Soranoiseki\BookGroup\Controllers\SongController;
use Soranoiseki\BookGroup\Controllers\SongApiController;
use Soranoiseki\BookGroup\Controllers\TaskPlanController;
use Soranoiseki\BookGroup\Controllers\TaskPlanApiController;


Route::middleware('web', 'auth')->group(function () {
    Route::group(['prefix' => 'ppt', 'middleware' => 'role:ppt'], function() {
        Route::get('/', [PowerpointController::class, 'index'])->name('book-group.ppt.index');
        Route::post('/save', [PowerpointController::class, 'store'])->name('book-group.ppt.store');
        Route::post('/download', [PowerpointController::class, 'download'])->name('book-group.ppt.download');
        Route::post('/save-download', [PowerpointController::class, 'storeAndDownload'])->name('book-group.ppt.store-download');

        Route::group(['prefix' => 'ajax'], function() {
            Route::get('/song', [PowerpointAjaxController::class, 'getSong'])->name('book-group.ppt.ajax.get-song');
        });
    });

    Route::group(['prefix' => 'songs', 'middleware' => 'role:songs_management'], function() {
        Route::get('/', [SongController::class, 'index'])->name('book-group.song.index');
        Route::get('/{song}', [SongController::class, 'edit'])->name('book-group.song.edit');
        Route::post('/{song}', [SongController::class, 'save'])->name('book-group.song.save');
        Route::get('/{song}/delete', [SongController::class, 'delete'])->name('book-group.song.delete');
        Route::get('/upload/{songContent}', [SongController::class, 'upload'])->name('book-group.song-content.upload');
    });
    
    Route::group(['prefix' => 'library', 'middleware' => 'role:library'], function() {
        Route::get('/', [LibraryController::class, 'index'])->name('library.index');

        Route::get('/book/{bookId}/{copyId}/borrow', [LibraryController::class, 'borrowBook'])->name('library.book.borrow');
        Route::get('/book/{bookId}/{copyId}/return', [LibraryController::class, 'returnBook'])->name('library.book.return');
        // Route::get('/book/');

        Route::get('/import', [LibraryController::class, 'importBooks'])->name('library.import');
        Route::post('/import/books', [LibraryController::class, 'importBooks'])->name('library.import.books');
        Route::post('/import/members', [LibraryController::class, 'importMembers'])->name('library.import.members');
    });

    Route::group(['prefix' => 'calendar', 'middleware' => 'role:calendar'], function() {
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

    Route::group(['prefix' => 'task-plan'], function() {
        Route::get('/', [TaskPlanController::class, 'index'])->name('book-group.task-plan.index');
    });

    Route::group(['prefix' => 'api/task-plan'], function() {
        Route::get('/members', [TaskPlanApiController::class, 'getMembers'])->name('book-group.task-plan.api.get-members');
        Route::post('/members', [TaskPlanApiController::class, 'createMember'])->name('book-group.task-plan.api.create-member');
        Route::post('/members/delete', [TaskPlanApiController::class, 'deleteMember'])->name('book-group.task-plan.api.delete-member');
        Route::get('/groups', [TaskPlanApiController::class, 'getGroups'])->name('book-group.task-plan.api.get-groups');
        Route::post('/groups/roles/toggle', [TaskPlanApiController::class, 'toggleGroupRole'])->name('book-group.task-plan.api.toggle-group-role');

        Route::get('/plans', [TaskPlanApiController::class, 'getTaskPlans'])->name('book-group.task-plan.api.index');
        Route::put('/plans', [TaskPlanApiController::class, 'updateTaskPlan'])->name('book-group.task-plan.api.update');
        Route::get('/plans/text', [TaskPlanApiController::class, 'getTaskPlansText'])->name('book-group.task-plan.api.get-text');
    });
    
});

// Public routes
Route::middleware('web')->group(function () {
    Route::get('/worship-songs', [SongController::class, 'list'])->name('book-group.song.list');
    Route::get('/api/songs', [SongApiController::class, 'index']);
    Route::get('/api/songs/{groupId}', [SongApiController::class, 'show']);
});
