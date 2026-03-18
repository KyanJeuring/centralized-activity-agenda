<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Throwable;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // 1. PUBLIC TEST ROUTE (Partner's database check)
    Route::get('/test-endpoint', function () {
        try {
            // Updated to be "Sandbox Friendly" for your SQLite setup
            $dbName = config('database.default') === 'sqlite' 
                ? 'SQLite Sandbox' 
                : DB::selectOne('SELECT current_database() AS db')->db;

            return response()->json([
                'status' => 'online',
                'database' => $dbName,
                'version' => '1.0.0'
            ]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // 2. PROTECTED CLIENT ROUTES (The "Agenda" Logic)
    // This group requires the Bearer Token you generated
    Route::middleware('auth:api')->group(function () {
        
        Route::get('/agenda', function (Request $request) {
            return response()->json([
                'client_email' => $request->user()->email,
                'agenda_items' => [
                    ['id' => 101, 'title' => 'Initial Sync', 'time' => '09:00'],
                    ['id' => 102, 'title' => 'Data Migration', 'time' => '13:00'],
                ]
            ]);
        });

        // This is where you'll eventually add POST/PUT routes for the agenda
    });

});