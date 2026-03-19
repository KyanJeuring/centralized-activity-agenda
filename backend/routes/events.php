<?php

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Throwable;

Route::prefix('api/v1')->group(function () {
    Route::get('test-endpoint', function () {
        try {
            $result = DB::selectOne('SELECT current_database() AS database, current_user AS username');

            return response(
                "[200] Laravel backend is up. Connected to {$result->database} as {$result->username}.",
                Response::HTTP_OK,
                ['Content-Type' => 'text/plain']
            );
        } catch (Throwable $exception) {
            return response(
                '[500] ' . $exception->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['Content-Type' => 'text/plain']
            );
        }
    });

    Route::get('events', function () {
        return response()->json([
            ['code' => 200, 'description' => 'This end point will return a list of all events.'],
        ]);
    });

    Route::get('events/upcoming', function () {
        return response()->json([
            ['code' => 200, 'description' => 'This end point will return a list of all upcoming events.'],
        ]);
    });

    Route::get('events/past', function () {
        return response()->json([
            ['code' => 200, 'description' => 'This end point will return a list of all past events.'],
        ]);
    });

    Route::get('events/{id}', function ($id) {
        return response()->json([
            ['code' => 200, 'description' => "This end point will return the details of the event with ID: $id."],
            ['code' => 404, 'description' => "This end point will return a 404 error if no event with ID: $id is found."],
        ]);
    })->whereNumber('id');

    Route::post('events', function () {
        return response()->json([
            ['code' => 201, 'description' => 'This end point will create a new event and return its details.'],
            ['code' => 400, 'description' => 'This end point will return a 400 error if the request data is invalid.'],
        ]);
    });

    Route::put('events/{id}', function ($id) {
        return response()->json([
            ['code' => 200, 'description' => "This end point will update the event with ID: $id and return its updated details."],
            ['code' => 400, 'description' => "This end point will return a 400 error if the request data is invalid."],
            ['code' => 404, 'description' => "This end point will return a 404 error if no event with ID: $id is found."],
        ]);
    })->whereNumber('id');

    Route::patch('events/{id}/cancel', function ($id) {
        return response()->json([
            ['code' => 200, 'description' => "This end point will cancel the event with ID: $id and return its updated details."],
            ['code' => 404, 'description' => "This end point will return a 404 error if no event with ID: $id is found."],
        ]);
    })->whereNumber('id');

    Route::patch('events/{id}', function ($id) {
        return response()->json([
            ['code' => 200, 'description' => "This end point will partially update the event with ID: $id and return its updated details."],
            ['code' => 400, 'description' => "This end point will return a 400 error if the request data is invalid."],
            ['code' => 404, 'description' => "This end point will return a 404 error if no event with ID: $id is found."],
        ]);
    })->whereNumber('id');

    Route::delete('events/{id}', function ($id) {
        return response()->json([
            ['code' => 200, 'description' => "This end point will delete the event with ID: $id and return a confirmation message."],
            ['code' => 404, 'description' => "This end point will return a 404 error if no event with ID: $id is found."],
        ]);
    })->whereNumber('id');
});
