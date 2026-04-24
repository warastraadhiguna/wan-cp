<?php

use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/uploads/{path}', [HomeController::class, 'showUpload'])
    ->where('path', '.*')
    ->name('uploads.show');

Route::post('/contact-messages', [ContactMessageController::class, 'store'])
    ->middleware('throttle:contact-messages')
    ->name('contact-messages.store');

Route::view('/tools/image-editor', 'tools.image-editor')->name('tools.image-editor');
Route::view('/tools/sunday-school-game', 'tools.sunday-school-game')->name('tools.sunday-school-game');

Route::get('/', HomeController::class)->name('home');
Route::get('/home', HomeController::class);
