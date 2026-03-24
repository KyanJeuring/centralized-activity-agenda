<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;
use Throwable;

class EventController extends Controller
{
    public function testEndpoint()
    {
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
    }

    #[OA\Get(
        path: '/v1/events',
        operationId: 'getUpcomingEvents',
        tags: ['Events'],
        summary: 'Get upcoming events',
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Case-insensitive search across name, organizer, description, location, url, and app_name',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of upcoming events',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            ),
        ]
    )]
    public function index(Request $request)
    {
        $query = DB::table('vw_events_upcoming');

        $this->applySearchFilter($query, $request->query('search'));

        $events = $query
            ->orderByRaw('start_date IS NULL, start_date ASC')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($events, Response::HTTP_OK);
    }

    #[OA\Get(
        path: '/v1/events/all',
        operationId: 'getAllEvents',
        tags: ['Events'],
        summary: 'Get all events',
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Case-insensitive search across name, organizer, description, location, url, and app_name',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of all events',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            ),
        ]
    )]
    public function all(Request $request)
    {
        $query = DB::table('vw_events_all');

        $this->applySearchFilter($query, $request->query('search'));

        $events = $query
            ->orderBy('start_date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($events, Response::HTTP_OK);
    }

    #[OA\Get(
        path: '/v1/events/past',
        operationId: 'getPastEvents',
        tags: ['Events'],
        summary: 'Get past events',
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Case-insensitive search across name, organizer, description, location, url, and app_name',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of past events',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            ),
        ]
    )]
    public function past(Request $request)
    {
        $query = DB::table('vw_events_past');

        $this->applySearchFilter($query, $request->query('search'));

        $events = $query
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($events, Response::HTTP_OK);
    }

    #[OA\Get(
        path: '/v1/events/{id}',
        operationId: 'getEventById',
        tags: ['Events'],
        summary: 'Get one event by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Event found', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 404, description: 'Event not found'),
        ]
    )]
    public function show(string $id)
    {
        $event = DB::table('vw_event_details')
            ->where('id', $id)
            ->first();

        if (! $event) {
            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($event, Response::HTTP_OK);
    }

    #[OA\Post(
        path: '/v1/events',
        operationId: 'createEvent',
        tags: ['Events'],
        summary: 'Create a new event',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['organizer', 'description', 'url', 'app_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'organizer', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'url', type: 'string'),
                    new OA\Property(property: 'app_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'location', type: 'string', nullable: true),
                    new OA\Property(property: 'img', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Event created', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required_without:title|string',
            'title' => 'required_without:name|string',
            'organizer' => 'required|string',
            'description' => 'required|string',
            'url' => 'required|string',
            'app_id' => 'required|uuid|exists:clubs,id',
            'start_date' => 'nullable|date',
            'location' => 'nullable|string',
            'img' => 'nullable|string',
        ]);

        $id = (string) Str::uuid();

        DB::table('events')->insert([
            'id' => $id,
            'name' => $data['name'] ?? $data['title'],
            'organizer' => $data['organizer'],
            'start_date' => $data['start_date'] ?? null,
            'description' => $data['description'],
            'location' => $data['location'] ?? null,
            'url' => $data['url'],
            'app_id' => $data['app_id'],
            'img' => $data['img'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $event = DB::table('events as e')
            ->join('clubs as c', 'c.id', '=', 'e.app_id')
            ->where('e.id', $id)
            ->select([
                'e.id',
                'e.name',
                'e.organizer',
                'e.start_date',
                'e.description',
                'e.location',
                'e.url',
                'e.app_id',
                'c.name as app_name',
                'c.source as app_source',
                'c.type as app_type',
                'e.img',
                'e.created_at',
                'e.updated_at',
            ])
            ->first();

        return response()->json($event, Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: '/v1/events/{id}',
        operationId: 'replaceEvent',
        tags: ['Events'],
        summary: 'Replace an event by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['organizer', 'description', 'url', 'app_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'organizer', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'url', type: 'string'),
                    new OA\Property(property: 'app_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'location', type: 'string', nullable: true),
                    new OA\Property(property: 'img', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Event updated'),
            new OA\Response(response: 404, description: 'Event not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, string $id)
    {
        if (! DB::table('events')->where('id', $id)->exists()) {
            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'name' => 'required_without:title|string',
            'title' => 'required_without:name|string',
            'organizer' => 'required|string',
            'description' => 'required|string',
            'url' => 'required|string',
            'app_id' => 'required|uuid|exists:clubs,id',
            'start_date' => 'nullable|date',
            'location' => 'nullable|string',
            'img' => 'nullable|string',
        ]);

        DB::table('events')
            ->where('id', $id)
            ->update([
                'name' => $data['name'] ?? $data['title'],
                'organizer' => $data['organizer'],
                'start_date' => $data['start_date'] ?? null,
                'description' => $data['description'],
                'location' => $data['location'] ?? null,
                'url' => $data['url'],
                'app_id' => $data['app_id'],
                'img' => $data['img'] ?? null,
                'updated_at' => now(),
            ]);

        return response()->json(['message' => 'Event updated successfully.'], Response::HTTP_OK);
    }

    #[OA\Patch(
        path: '/v1/events/{id}/cancel',
        operationId: 'cancelEvent',
        tags: ['Events'],
        summary: 'Cancel an event by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Event cancelled'),
            new OA\Response(response: 404, description: 'Event not found'),
        ]
    )]
    public function cancel(string $id)
    {
        if (! DB::table('events')->where('id', $id)->exists()) {
            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($id) {
            $tagId = DB::table('tags')
                ->whereRaw('LOWER(slug) = ?', ['cancelled'])
                ->value('id');

            if (! $tagId) {
                $nextId = (DB::table('tags')->max('id') ?? 0) + 1;
                DB::table('tags')->insert([
                    'id' => $nextId,
                    'slug' => 'cancelled',
                ]);
                $tagId = $nextId;
            }

            $alreadyTagged = DB::table('event_tag')
                ->where('event_id', $id)
                ->where('tag_id', $tagId)
                ->exists();

            if (! $alreadyTagged) {
                $nextRelationId = (DB::table('event_tag')->max('id') ?? 0) + 1;
                DB::table('event_tag')->insert([
                    'id' => $nextRelationId,
                    'event_id' => $id,
                    'tag_id' => $tagId,
                ]);
            }

            DB::table('events')
                ->where('id', $id)
                ->update(['updated_at' => now()]);
        });

        return response()->json([
            'message' => 'Event cancelled successfully.',
        ], Response::HTTP_OK);
    }

    #[OA\Patch(
        path: '/v1/events/{id}',
        operationId: 'updateEventPartial',
        tags: ['Events'],
        summary: 'Partially update an event by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'organizer', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'url', type: 'string'),
                    new OA\Property(property: 'app_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'location', type: 'string', nullable: true),
                    new OA\Property(property: 'img', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Event updated'),
            new OA\Response(response: 400, description: 'No valid fields provided'),
            new OA\Response(response: 404, description: 'Event not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function partialUpdate(Request $request, string $id)
    {
        if (! DB::table('events')->where('id', $id)->exists()) {
            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'name' => 'sometimes|string',
            'title' => 'sometimes|string',
            'organizer' => 'sometimes|string',
            'description' => 'sometimes|string',
            'url' => 'sometimes|string',
            'app_id' => 'sometimes|uuid|exists:clubs,id',
            'start_date' => 'sometimes|nullable|date',
            'location' => 'sometimes|nullable|string',
            'img' => 'sometimes|nullable|string',
        ]);

        $updates = [];
        if (array_key_exists('name', $data) || array_key_exists('title', $data)) {
            $updates['name'] = $data['name'] ?? $data['title'];
        }

        foreach (['organizer', 'description', 'url', 'app_id', 'start_date', 'location', 'img'] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return response()->json([
                'message' => 'No valid fields were provided for update.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $updates['updated_at'] = now();

        DB::table('events')
            ->where('id', $id)
            ->update($updates);

        return response()->json(['message' => 'Event updated successfully.'], Response::HTTP_OK);
    }

    #[OA\Delete(
        path: '/v1/events/{id}',
        operationId: 'deleteEvent',
        tags: ['Events'],
        summary: 'Delete an event by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Event deleted'),
            new OA\Response(response: 404, description: 'Event not found'),
        ]
    )]
    public function destroy(string $id)
    {
        if (! DB::table('events')->where('id', $id)->exists()) {
            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($id) {
            DB::table('event_tag')->where('event_id', $id)->delete();
            DB::table('ext_int_ids')->where('event_id', $id)->delete();
            DB::table('events')->where('id', $id)->delete();
        });

        return response()->json([
            'message' => 'Event deleted successfully.',
        ], Response::HTTP_OK);
    }

    private function applySearchFilter($query, ?string $search): void
    {
        $term = trim((string) $search);

        if ($term === '') {
            return;
        }

        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], Str::lower($term));
        $pattern = "%{$escaped}%";

        $query->where(function ($subQuery) use ($pattern) {
            $subQuery->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereRaw("LOWER(organizer) LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereRaw("LOWER(description) LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereRaw("LOWER(location) LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereRaw("LOWER(url) LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereRaw("LOWER(app_name) LIKE ? ESCAPE '!'", [$pattern]);
        });
    }
}
