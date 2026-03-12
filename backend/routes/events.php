<?php

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Throwable;

Route::get('/api/test-endpoint', function () {
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
