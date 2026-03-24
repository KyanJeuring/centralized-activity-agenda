<?php

use App\Http\Controllers\Api\V1\EventController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->group(function () {
    Route::get('test-endpoint', [EventController::class, 'testEndpoint']);
    Route::get('events', [EventController::class, 'index']);
    Route::get('events/all', [EventController::class, 'all']);
    Route::get('events/past', [EventController::class, 'past']);
    Route::get('events/{id}', [EventController::class, 'show'])->whereUuid('id');
    Route::post('events', [EventController::class, 'store']);
    Route::put('events/{id}', [EventController::class, 'update'])->whereUuid('id');
    Route::patch('events/{id}/cancel', [EventController::class, 'cancel'])->whereUuid('id');
    Route::patch('events/{id}', [EventController::class, 'partialUpdate'])->whereUuid('id');
    Route::delete('events/{id}', [EventController::class, 'destroy'])->whereUuid('id');
});
