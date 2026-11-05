<?php

use Illuminate\Support\Facades\Route;
use Soranoiseki\BookGroup\Http\Middleware\VerifyWebsiteApiToken;
use Soranoiseki\BookGroup\Http\Controllers\PowerpointController;
use Soranoiseki\BookGroup\Http\Controllers\PowerpointAjaxController;
use Soranoiseki\BookGroup\Http\Controllers\PowerpointApiController;
use Soranoiseki\BookGroup\Http\Controllers\LibraryController;
use Soranoiseki\BookGroup\Http\Controllers\CalendarController;
use Soranoiseki\BookGroup\Http\Controllers\CalendarApiController;
use Soranoiseki\BookGroup\Http\Controllers\SongController;
use Soranoiseki\BookGroup\Http\Controllers\SongApiController;
use Soranoiseki\BookGroup\Http\Controllers\TaskPlanController;
use Soranoiseki\BookGroup\Http\Controllers\TaskPlanApiController;
use Soranoiseki\BookGroup\Http\Controllers\Api\WebsiteApiController;


Route::middleware(['web', 'auth'])->group(function () {
    Route::group(['prefix' => 'ppt', 'middleware' => 'role:ppt'], function() {
        Route::get('/', [PowerpointController::class, 'index'])->name('book-group.ppt.index');
        Route::post('/save', [PowerpointController::class, 'store'])->name('book-group.ppt.store');
        Route::post('/download', [PowerpointController::class, 'download'])->name('book-group.ppt.download');
        Route::post('/save-download', [PowerpointController::class, 'storeAndDownload'])->name('book-group.ppt.store-download');

        Route::group(['prefix' => 'ajax'], function() {
            Route::get('/song', [PowerpointAjaxController::class, 'getSong'])->name('book-group.ppt.ajax.get-song');
        });
    });

    Route::group(['prefix' => 'api/powerpoint', 'middleware' => 'role:ppt'], function() {
        Route::get('/', [PowerpointApiController::class, 'show'])->name('book-group.ppt.api.show');
        Route::post('/save', [PowerpointApiController::class, 'save'])->name('book-group.ppt.api.save');
        Route::post('/save-download', [PowerpointApiController::class, 'saveDownload'])->name('book-group.ppt.api.save-download');
        Route::post('/download', [PowerpointApiController::class, 'download'])->name('book-group.ppt.api.download');
        Route::get('/songs', [PowerpointApiController::class, 'songs'])->name('book-group.ppt.api.songs');
        Route::get('/song', [PowerpointApiController::class, 'getSong'])->name('book-group.ppt.api.song');
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
    });

    Route::group(['prefix' => 'api/calendar', 'middleware' => 'role:calendar'], function() {
        Route::get('/', [CalendarApiController::class, 'show'])->name('book-group.calendar.api.show');
        Route::post('/', [CalendarApiController::class, 'create'])->name('book-group.calendar.api.create');
        Route::post('/{calendar}/events', [CalendarApiController::class, 'importEvents'])->where('calendar', '[0-9]+')->name('book-group.calendar.api.import-events');
        Route::post('/{calendar}/bible-texts', [CalendarApiController::class, 'importBibleTexts'])->where('calendar', '[0-9]+')->name('book-group.calendar.api.import-bible-texts');
        Route::post('/{calendar}/images/{slot}', [CalendarApiController::class, 'uploadImage'])->where('calendar', '[0-9]+')->name('book-group.calendar.api.upload-image');
        Route::delete('/{calendar}/images/{slot}', [CalendarApiController::class, 'deleteImage'])->where('calendar', '[0-9]+')->name('book-group.calendar.api.delete-image');
        Route::post('/{calendar}/holidays', [CalendarApiController::class, 'refreshHolidays'])->where('calendar', '[0-9]+')->name('book-group.calendar.api.holidays');
        Route::post('/{calendar}/pdf', [CalendarApiController::class, 'pdf'])->where('calendar', '[0-9]+')->name('book-group.calendar.api.pdf');
    });

    Route::group(['prefix' => 'planer', 'middleware' => 'role.wildcard:planer*'], function() {
        Route::get('/', [TaskPlanController::class, 'index'])->name('book-group.task-plan.index');
    });

    Route::group(['prefix' => 'api/planer', 'middleware' => 'role.wildcard:planer*'], function() {
        Route::get('/members', [TaskPlanApiController::class, 'getMembers'])->name('book-group.task-plan.api.get-members');
        Route::post('/members', [TaskPlanApiController::class, 'createMember'])->name('book-group.task-plan.api.create-member');
        Route::post('/members/delete', [TaskPlanApiController::class, 'deleteMember'])->name('book-group.task-plan.api.delete-member');
        Route::get('/groups', [TaskPlanApiController::class, 'getGroups'])->name('book-group.task-plan.api.get-groups');
        Route::post('/groups/roles/toggle', [TaskPlanApiController::class, 'toggleGroupRole'])->name('book-group.task-plan.api.toggle-group-role');

        Route::get('/plans', [TaskPlanApiController::class, 'getTaskPlans'])->name('book-group.task-plan.api.index');
        Route::put('/plans', [TaskPlanApiController::class, 'updateTaskPlan'])->name('book-group.task-plan.api.update');
    });

    Route::get('api/planer/plans/text', [TaskPlanApiController::class, 'getTaskPlansText'])->name('book-group.task-plan.api.get-text');
    
});

// Public routes
Route::middleware('web')->group(function () {
    Route::get('/worship-songs', [SongController::class, 'list'])->name('book-group.song.list');
    Route::get('/api/songs', [SongApiController::class, 'index']);
    Route::get('/api/songs/{groupId}', [SongApiController::class, 'show']);
});


// Route::middleware(['api', VerifyWebsiteApiToken::class])->group(function () {
Route::middleware(['api'])->group(function () {
    Route::group(['prefix' => 'api/website'], function() {
        Route::get('/plans/{year}', [WebsiteApiController::class, 'getPlansByYear'])->where('year', '[0-9]+');
        Route::get('/bulletins/{year}', [WebsiteApiController::class, 'getBulletinsByYear'])->where('year', '[0-9]+');
        Route::get('/finance', [WebsiteApiController::class, 'getLatestFinance']);
    });
});
