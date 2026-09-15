<?php

use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KoKiController;
use App\Http\Controllers\KoKiMeetingTwoController;
use App\Http\Controllers\KoKiMeetingThreeController;
use App\Http\Controllers\KoKiMeetingFourController;
use Illuminate\Support\Facades\Route;

Route::get('/uploads/{path}', [HomeController::class, 'showUpload'])
    ->where('path', '.*')
    ->name('uploads.show');

Route::post('/contact-messages', [ContactMessageController::class, 'store'])
    ->middleware('throttle:contact-messages')
    ->name('contact-messages.store');

Route::view('/tools/image-editor', 'tools.image-editor')->name('tools.image-editor');

Route::prefix('/tools/wan-ko-ki')->name('tools.ko-ki.')->controller(KoKiController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::get('/pertemuan-1', 'welcome')->name('welcome');
    Route::post('/mulai', 'start')->name('start');
    Route::get('/pembukaan', 'opening')->name('opening');
    Route::post('/pertanyaan/mulai', 'beginQuestions')->name('questions.start');
    Route::get('/pertanyaan/selesai', 'questionsCompleted')->name('questions.completed');
    Route::get('/pertanyaan/{question}', 'question')->whereNumber('question')->name('questions.show');
    Route::post('/pertanyaan/{question}', 'answer')->whereNumber('question')->name('questions.answer');
    Route::post('/aktivitas/mulai', 'beginActivity')->name('activity.start');
    Route::get('/aktivitas', 'activity')->name('activity');
    Route::patch('/progress', 'update')->name('progress.update');
    Route::get('/misi/status', 'levelStatus')->middleware('throttle:60,1')->name('levels.status');
    Route::get('/laboratorium', 'laboratory')->name('laboratory');
    Route::patch('/laboratorium/progress', 'updateLaboratory')->name('laboratory.update');
    Route::post('/selesai', 'finish')->name('finish');
    Route::get('/selesai', 'completed')->name('completed');
});

Route::prefix('/tools/wan-ko-ki/pertemuan-2')
    ->name('tools.ko-ki.meeting-two.')
    ->controller(KoKiMeetingTwoController::class)
    ->group(function (): void {
        Route::get('/', 'welcome')->name('welcome');
        Route::post('/mulai', 'start')->name('start');
        Route::get('/aktivitas', 'activity')->name('activity');
        Route::get('/peta', 'map')->name('map');
        Route::get('/lokasi/{location}', 'mission')->name('mission');
        Route::patch('/progress', 'update')->name('update');
        Route::get('/selesai', 'completed')->name('completed');
    });

Route::prefix('/tools/wan-ko-ki/pertemuan-3')
    ->name('tools.ko-ki.meeting-three.')
    ->controller(KoKiMeetingThreeController::class)
    ->group(function (): void {
        Route::get('/', 'welcome')->name('welcome');
        Route::post('/mulai', 'start')->name('start');
        Route::get('/pendahuluan', 'intro')->name('intro');
        Route::post('/pendahuluan/selesai', 'completeIntro')->name('intro.complete');
        Route::get('/peta', 'map')->name('map');
        Route::get('/misi/{mission}', 'mission')->whereNumber('mission')->name('mission');
        Route::patch('/progress', 'completeLevel')->name('progress');
        Route::get('/evaluasi', 'evaluation')->name('evaluation');
        Route::post('/evaluasi', 'submitEvaluation')->name('evaluation.submit');
    });

Route::prefix('/tools/wan-ko-ki/pertemuan-4')
    ->name('tools.ko-ki.meeting-four.')
    ->controller(KoKiMeetingFourController::class)
    ->group(function (): void {
        Route::get('/', 'welcome')->name('welcome');
        Route::post('/mulai', 'start')->name('start');
        Route::get('/pendahuluan', 'intro')->name('intro');
        Route::post('/pendahuluan', 'begin')->name('intro.begin');
        Route::get('/peta-kasus', 'map')->name('map');
        Route::get('/kasus/{case}', 'case')->whereNumber('case')->name('case');
        Route::patch('/progress', 'complete')->name('progress');
        Route::post('/sync', 'sync')->name('sync');
        Route::get('/detective-notebook', 'notebook')->name('notebook');
        Route::post('/detective-notebook', 'completeNotebook')->name('notebook.complete');
    });

Route::get('/', HomeController::class)->name('home');
Route::get('/home', HomeController::class);
