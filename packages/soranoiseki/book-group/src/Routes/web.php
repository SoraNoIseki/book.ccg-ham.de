<?php

use Illuminate\Support\Facades\Route;
use Soranoiseki\BookGroup\Http\Middleware\VerifyWebsiteApiToken;
use Soranoiseki\BookGroup\Http\Controllers\PowerpointController;
use Soranoiseki\BookGroup\Http\Controllers\PowerpointAjaxController;
use Soranoiseki\BookGroup\Http\Controllers\PowerpointApiController;
use Soranoiseki\BookGroup\Http\Controllers\LibraryController;
use Soranoiseki\BookGroup\Http\Controllers\LibraryApiController;
use Soranoiseki\BookGroup\Http\Controllers\CalendarController;
use Soranoiseki\BookGroup\Http\Controllers\CalendarApiController;
use Soranoiseki\BookGroup\Http\Controllers\CalendarDesignController;
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
    });

    Route::group(['prefix' => 'api/library', 'middleware' => 'role:library'], function() {
        Route::get('/summary', [LibraryApiController::class, 'summary'])->name('library.api.summary');
        Route::get('/books', [LibraryApiController::class, 'books'])->name('library.api.books');
        Route::post('/books', [LibraryApiController::class, 'saveBook'])->name('library.api.books.save');
        Route::patch('/books/{book}', [LibraryApiController::class, 'updateBook'])->where('book', '[0-9]+')->name('library.api.books.update');
        Route::post('/books/{book}/copies', [LibraryApiController::class, 'addCopy'])->where('book', '[0-9]+')->name('library.api.books.add-copy');
        Route::get('/lookup', [LibraryApiController::class, 'lookup'])->name('library.api.lookup');
        Route::get('/metadata', [LibraryApiController::class, 'metadata'])->name('library.api.metadata');
        Route::post('/copies/{copy}/borrow', [LibraryApiController::class, 'borrow'])->where('copy', '[0-9]+')->name('library.api.borrow');
        Route::post('/copies/{copy}/return', [LibraryApiController::class, 'return'])->where('copy', '[0-9]+')->name('library.api.return');
        Route::post('/loans/{loan}/renew', [LibraryApiController::class, 'renew'])->where('loan', '[0-9]+')->name('library.api.renew');
        Route::get('/loans', [LibraryApiController::class, 'loans'])->name('library.api.loans');
        Route::get('/members', [LibraryApiController::class, 'members'])->name('library.api.members');
        Route::post('/members', [LibraryApiController::class, 'createMember'])->name('library.api.members.create');
        Route::post('/import/books', [LibraryApiController::class, 'importBooks'])->name('library.api.import-books');
    });

    Route::group(['prefix' => 'calendar', 'middleware' => 'role:calendar'], function() {
        Route::get('/', [CalendarController::class, 'index'])->name('book-group.calendar.index');
    });

    Route::group(['prefix' => 'api/calendar', 'middleware' => 'role:calendar'], function() {
        Route::get('/designs', [CalendarDesignController::class, 'index'])->name('book-group.calendar.api.designs');
        Route::post('/designs', [CalendarDesignController::class, 'store'])->name('book-group.calendar.api.designs.store');
        Route::get('/designs/{design}', [CalendarDesignController::class, 'show'])->where('design', '[0-9]+')->name('book-group.calendar.api.designs.show');
        Route::patch('/designs/{design}', [CalendarDesignController::class, 'rename'])->where('design', '[0-9]+')->name('book-group.calendar.api.designs.rename');
        Route::put('/designs/{design}/content', [CalendarDesignController::class, 'update'])->where('design', '[0-9]+')->name('book-group.calendar.api.designs.update');
        Route::delete('/designs/{design}', [CalendarDesignController::class, 'destroy'])->where('design', '[0-9]+')->name('book-group.calendar.api.designs.destroy');
        Route::post('/designs/{design}/restore/{revision}', [CalendarDesignController::class, 'restore'])->where(['design' => '[0-9]+', 'revision' => '[0-9]+'])->name('book-group.calendar.api.designs.restore');

        Route::get('/', [CalendarApiController::class, 'show'])->name('book-group.calendar.api.show');
        Route::post('/', [CalendarApiController::class, 'create'])->name('book-group.calendar.api.create');
        Route::post('/{calendar}/events', [CalendarApiController::class, 'importEvents'])->where('calendar', '[0-9]+')->name('book-group.calendar.api.import-events');
        Route::post('/{calendar}/bible-texts', [CalendarApiController::class, 'importBibleTexts'])->where('calendar', '[0-9]+')->name('book-group.calendar.api.import-bible-texts');
        Route::post('/{calendar}/images/{slot}', [CalendarApiController::class, 'uploadImage'])->where('calendar', '[0-9]+')->name('book-group.calendar.api.upload-image');
        Route::delete('/{calendar}/images/{slot}', [CalendarApiController::class, 'deleteImage'])->where('calendar', '[0-9]+')->name('book-group.calendar.api.delete-image');
        Route::post('/{calendar}/holidays', [CalendarApiController::class, 'refreshHolidays'])->where('calendar', '[0-9]+')->name('book-group.calendar.api.holidays');
        Route::put('/{calendar}/use-design', [CalendarApiController::class, 'useDesign'])->where('calendar', '[0-9]+')->name('book-group.calendar.api.use-design');
        Route::post('/{calendar}/preview', [CalendarApiController::class, 'preview'])->where('calendar', '[0-9]+')->name('book-group.calendar.api.preview');
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
