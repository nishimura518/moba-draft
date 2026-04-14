<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoomPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::post('/rooms', [HomeController::class, 'createRoom'])->middleware('throttle:create-room');

Route::get('/rooms/{room:uuid}', [RoomPageController::class, 'show']);
