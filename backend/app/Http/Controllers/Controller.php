<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

abstract class Controller
{
    protected function storedProcedureErrorResponse(QueryException $exception): JsonResponse
    {
        $message = $this->extractPostgresErrorMessage($exception);
        $normalized = Str::lower($message);
        $status = Response::HTTP_UNPROCESSABLE_ENTITY;

        if (str_contains($normalized, 'already exists')) {
            $status = Response::HTTP_CONFLICT;
        }

        if (str_contains($normalized, 'does not exist')) {
            $status = Response::HTTP_NOT_FOUND;
        }

        return response()->json([
            'message' => $message,
        ], $status);
    }

    protected function extractPostgresErrorMessage(QueryException $exception): string
    {
        $dbMessage = $exception->errorInfo[2] ?? $exception->getMessage();

        if (preg_match('/ERROR:\\s*(.+?)(?:\\n|$)/', $dbMessage, $matches)) {
            return trim($matches[1]);
        }

        return 'Database operation failed.';
    }
}
