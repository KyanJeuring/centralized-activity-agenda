<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use Throwable;

class SystemController extends Controller
{
    #[OA\Get(
        path: '/v1/test-endpoint',
        operationId: 'getTestEndpoint',
        tags: ['Health'],
        summary: 'Check API and database connectivity',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service is online',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'online'),
                        new OA\Property(property: 'database', type: 'string', example: 'SQLite Sandbox'),
                        new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Unexpected server error'),
        ]
    )]
    public function testEndpoint()
    {
        try {
            $dbName = config('database.default') === 'sqlite'
                ? 'SQLite Sandbox'
                : DB::selectOne('SELECT current_database() AS db')->db;

            $this->logAction('System connectivity check succeeded', [
                'database' => $dbName,
            ]);

            return response()->json([
                'status' => 'online',
                'database' => $dbName,
                'version' => '1.0.0',
            ]);
        } catch (Throwable $e) {
            $this->logException($e, 'System connectivity check failed', [], 'critical');

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/v1/agenda',
        operationId: 'getAgenda',
        tags: ['Agenda'],
        summary: 'Get agenda items for the authenticated client',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Agenda data returned',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'client_email', type: 'string', example: 'client@example.com'),
                        new OA\Property(
                            property: 'agenda_items',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 101),
                                    new OA\Property(property: 'title', type: 'string', example: 'Initial Sync'),
                                    new OA\Property(property: 'time', type: 'string', example: '09:00'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function agenda(Request $request)
    {
        $this->logAction('Agenda requested', [
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
        ]);

        return response()->json([
            'client_email' => $request->user()->email,
            'agenda_items' => [
                ['id' => 101, 'title' => 'Initial Sync', 'time' => '09:00'],
                ['id' => 102, 'title' => 'Data Migration', 'time' => '13:00'],
            ],
        ]);
    }
}
