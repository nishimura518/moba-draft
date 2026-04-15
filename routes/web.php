<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoomPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

// GET /rooms はフォーム送信先と同じ URL のため、直アクセスは 404 になる。トップへ誘導する。
Route::get('/rooms', fn () => redirect('/'));

Route::post('/rooms', [HomeController::class, 'createRoom'])
    ->middleware('throttle:create-room')
    ->name('rooms.store');

Route::get('/rooms/{room:uuid}', [RoomPageController::class, 'show']);
