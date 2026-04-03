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
        $sqlState = $this->extractPostgresSqlState($exception);
        $status = $this->resolvePostgresStatusCode($sqlState, $message);

        $payload = [
            'message' => $message,
        ];

        if ($sqlState !== null) {
            $payload['code'] = $sqlState;
        }

        return response()->json($payload, $status);
    }

    protected function extractPostgresErrorMessage(QueryException $exception): string
    {
        $dbMessage = $exception->errorInfo[2] ?? $exception->getMessage();

        if (preg_match('/ERROR:\\s*(.+?)(?:\\n|$)/', $dbMessage, $matches)) {
            return trim($matches[1]);
        }

        if (is_string($dbMessage) && trim($dbMessage) !== '') {
            return trim($dbMessage);
        }

        return 'Database operation failed.';
    }

    protected function extractPostgresSqlState(QueryException $exception): ?string
    {
        $state = $exception->errorInfo[0] ?? null;

        if (is_string($state) && $state !== '') {
            return $state;
        }

        if (preg_match('/SQLSTATE\[([A-Z0-9]{5})\]/', $exception->getMessage(), $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function resolvePostgresStatusCode(?string $sqlState, string $message): int
    {
        $normalized = Str::lower($message);

        $statusFromState = match ($sqlState) {
            '23505' => Response::HTTP_CONFLICT,
            '40001', '40P01' => Response::HTTP_CONFLICT,
            '42501' => Response::HTTP_FORBIDDEN,
            '55P03' => Response::HTTP_LOCKED,
            '23502', '23503', '23514', '22P02', '22007', '22001' => Response::HTTP_UNPROCESSABLE_ENTITY,
            '42P01', '42703', '42601', '42883' => Response::HTTP_INTERNAL_SERVER_ERROR,
            default => null,
        };

        if ($statusFromState !== null) {
            return $statusFromState;
        }

        if (str_contains($normalized, 'already exists') || str_contains($normalized, 'duplicate key value')) {
            return Response::HTTP_CONFLICT;
        }

        if (str_contains($normalized, 'does not exist') || str_contains($normalized, 'not found')) {
            return Response::HTTP_NOT_FOUND;
        }

        if (
            str_contains($normalized, 'required')
            || str_contains($normalized, 'cannot be empty')
            || str_contains($normalized, 'must not be null')
            || str_contains($normalized, 'invalid input syntax')
            || str_contains($normalized, 'violates check constraint')
            || str_contains($normalized, 'violates foreign key constraint')
        ) {
            return Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        if (str_contains($normalized, 'permission denied')) {
            return Response::HTTP_FORBIDDEN;
        }

        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }
}
