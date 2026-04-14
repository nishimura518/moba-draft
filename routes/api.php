<?php

use App\Http\Controllers\Api\DraftController;
use App\Http\Controllers\Api\RoomController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:create-room')->post('/rooms', [RoomController::class, 'store']);

Route::middleware('throttle:api-join')->post('/rooms/{room:uuid}/join', [RoomController::class, 'join']);

Route::middleware('throttle:api-mutation')->group(function () {
    Route::post('/rooms/{room:uuid}/leave', [RoomController::class, 'leave']);
    Route::post('/rooms/{room:uuid}/draft/start', [DraftController::class, 'start']);
    Route::post('/rooms/{room:uuid}/draft/ban', [DraftController::class, 'ban']);
    Route::post('/rooms/{room:uuid}/draft/pick', [DraftController::class, 'pick']);
});

Route::middleware('throttle:api-read')->group(function () {
    Route::get('/rooms/{room:uuid}', [RoomController::class, 'show']);
    Route::get('/rooms/{room:uuid}/draft', [DraftController::class, 'show']);
});
