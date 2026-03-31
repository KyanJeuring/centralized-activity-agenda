<?php

use App\Http\Controllers\Api\V1\SystemController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/

Route::group([], function () {

    // 1. PUBLIC TEST ROUTE (Partner's database check)
    Route::get('/test-endpoint', [SystemController::class, 'testEndpoint']);

    // 2. PROTECTED CLIENT ROUTES (The "Agenda" Logic)
    // This group requires the Bearer Token you generated
    Route::middleware('auth:api')->group(function () {
        Route::get('/agenda', [SystemController::class, 'agenda']);

        // This is where you'll eventually add POST/PUT routes for the agenda
    });

});