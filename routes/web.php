<?php

use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KoKiController;
use Illuminate\Support\Facades\Route;

Route::get('/uploads/{path}', [HomeController::class, 'showUpload'])
    ->where('path', '.*')
    ->name('uploads.show');

Route::post('/contact-messages', [ContactMessageController::class, 'store'])
    ->middleware('throttle:contact-messages')
    ->name('contact-messages.store');

Route::view('/tools/image-editor', 'tools.image-editor')->name('tools.image-editor');

Route::prefix('/tools/wan-ko-ki')->name('tools.ko-ki.')->controller(KoKiController::class)->group(function (): void {
    Route::get('/', 'welcome')->name('welcome');
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

Route::get('/', HomeController::class)->name('home');
Route::get('/home', HomeController::class);
