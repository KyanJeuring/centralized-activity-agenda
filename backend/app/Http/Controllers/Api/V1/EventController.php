<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEventRequest;
use App\Http\Requests\Api\V1\UpdateEventRequest;
use App\Http\Resources\Api\V1\EventResource;
use App\Services\EventUrlResolver;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
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
            $this->logAction('Event backend connectivity check succeeded', [
                'database' => $result->database,
                'username' => $result->username,
            ]);

            return response(
                "[200] Laravel backend is up. Connected to {$result->database} as {$result->username}.",
                Response::HTTP_OK,
                ['Content-Type' => 'text/plain']
            );
        } catch (Throwable $exception) {
            $this->logException($exception, 'Event backend connectivity check failed', [], 'critical');

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
            ->cursorPaginate(50);

        return EventResource::collection($events);
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
            ->orderByRaw('start_date IS NULL, start_date ASC')
            ->orderByDesc('created_at')
            ->cursorPaginate(50);

        return EventResource::collection($events);
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
            ->cursorPaginate(50);

        return EventResource::collection($events);
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
        $event = DB::table('vw_event_details')->where('id', $id)->first();

        if (! $event) {
            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        return new EventResource($event);
    }

    #[OA\Post(
        path: '/v1/events',
        operationId: 'createEvent',
        tags: ['Events'],
        summary: 'Create a new event',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'organizer', 'description', 'url'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'organizer', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'url', type: 'string'),
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
    public function store(StoreEventRequest $request)
    {
        $userId = $this->authenticatedUserId($request);
        $data = $request->validated();

        $data['url'] = app(EventUrlResolver::class)->resolve($data['url']);

        $ownedClubId = $this->resolveDefaultOwnedClubId($userId);

        if ($ownedClubId === null) {
            $this->logAction('Event creation blocked because no owned club was found', [
                'user_id' => $userId,
                'title' => $data['title'] ?? null,
            ], 'warning');

            return response()->json([
                'message' => 'No club is linked to your account yet. Create/link a club before posting events.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = DB::selectOne(
                'SELECT sp_create_event(?, ?, ?, ?, ?, ?, ?, ?) AS event_id',
                [
                    $ownedClubId,
                    $data['title'],
                    $data['organizer'],
                    $data['description'],
                    $data['url'],
                    $this->toDatabaseDate($data['start_date'] ?? null),
                    $data['location'] ?? null,
                    $data['img'] ?? null,
                ]
            );
        } catch (QueryException $exception) {
            return $this->storedProcedureErrorResponse($exception);
        }

        $eventId = isset($result->event_id) ? (string) $result->event_id : null;

        if (! $eventId) {
            $this->logAction('Event creation returned no event id', [
                'user_id' => $userId,
                'club_id' => $ownedClubId,
                'title' => $data['title'] ?? null,
            ], 'error');

            return response()->json([
                'message' => 'Event could not be created.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $event = DB::table('vw_event_details')->where('id', $eventId)->first();

        if (! $event) {
            $this->logAction('Event was created but could not be reloaded', [
                'user_id' => $userId,
                'club_id' => $ownedClubId,
                'event_id' => $eventId,
            ], 'error');

            return response()->json([
                'message' => 'Event created but could not be loaded.',
            ], Response::HTTP_CREATED);
        }

        $this->logAction('Event created', [
            'user_id' => $userId,
            'club_id' => $ownedClubId,
            'event_id' => $eventId,
        ]);

        return response()->json(new EventResource($event), Response::HTTP_CREATED);
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
                required: ['title', 'organizer', 'description', 'url'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'organizer', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'url', type: 'string'),
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
    public function update(UpdateEventRequest $request, string $id)
    {
        $userId = $this->authenticatedUserId($request);
        $event = DB::table('events')->where('id', $id)->first();

        if (! $event) {
            $this->logAction('Event update requested for missing event', [
                'user_id' => $userId,
                'event_id' => $id,
            ], 'warning');

            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        if (! $this->isOwnedEvent($id, $userId)) {
            $this->logAction('Event update rejected because the event is not owned by the authenticated user', [
                'user_id' => $userId,
                'event_id' => $id,
            ], 'warning');

            return response()->json([
                'message' => 'You are not allowed to update this event.',
            ], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validated();

        $data['url'] = app(EventUrlResolver::class)->resolve($data['url']);

        try {
            $result = DB::selectOne(
                'SELECT sp_update_event(?, ?, ?, ?, ?, ?, ?, ?, ?) AS updated',
                [
                    $id,
                    $data['title'],
                    $data['organizer'],
                    $this->toDatabaseDate($data['start_date'] ?? null),
                    $data['description'],
                    $data['location'] ?? null,
                    $data['url'],
                    $data['img'] ?? null,
                    $event->app_id,
                ]
            );
        } catch (QueryException $exception) {
            return $this->storedProcedureErrorResponse($exception);
        }

        if (! $this->postgresBool($result->updated ?? false)) {
            $this->logAction('Event update returned no affected row', [
                'user_id' => $userId,
                'event_id' => $id,
            ], 'warning');

            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        $this->logAction('Event updated', [
            'user_id' => $userId,
            'event_id' => $id,
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
    public function cancel(Request $request, string $id)
    {
        $userId = $this->authenticatedUserId($request);

        if (! DB::table('events')->where('id', $id)->exists()) {
            $this->logAction('Event cancel requested for missing event', [
                'user_id' => $userId,
                'event_id' => $id,
            ], 'warning');

            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        if (! $this->isOwnedEvent($id, $userId)) {
            $this->logAction('Event cancel rejected because the event is not owned by the authenticated user', [
                'user_id' => $userId,
                'event_id' => $id,
            ], 'warning');

            return response()->json([
                'message' => 'You are not allowed to cancel this event.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $result = DB::selectOne('SELECT sp_cancel_event(?, ?) AS cancelled', [$id, true]);
        } catch (QueryException $exception) {
            return $this->storedProcedureErrorResponse($exception);
        }

        if (! $this->postgresBool($result->cancelled ?? false)) {
            $this->logAction('Event cancel returned no affected row', [
                'user_id' => $userId,
                'event_id' => $id,
            ], 'warning');

            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        $this->logAction('Event cancelled', [
            'user_id' => $userId,
            'event_id' => $id,
        ]);

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
        $userId = $this->authenticatedUserId($request);
        $event = DB::table('events')->where('id', $id)->first();

        if (! $event) {
            $this->logAction('Partial event update requested for missing event', [
                'user_id' => $userId,
                'event_id' => $id,
            ], 'warning');

            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        if (! $this->isOwnedEvent($id, $userId)) {
            $this->logAction('Partial event update rejected because the event is not owned by the authenticated user', [
                'user_id' => $userId,
                'event_id' => $id,
            ], 'warning');

            return response()->json([
                'message' => 'You are not allowed to update this event.',
            ], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'title' => 'sometimes|string',
            'organizer' => 'sometimes|string',
            'description' => 'sometimes|string',
            'url' => 'sometimes|url:http,https',
            'app_id' => 'sometimes|uuid|exists:clubs,id',
            'start_date' => 'sometimes|nullable|date',
            'location' => 'sometimes|nullable|string',
            'img' => 'sometimes|nullable|string',
        ]);

        if (array_key_exists('url', $data)) {
            $data['url'] = app(EventUrlResolver::class)->resolve($data['url']);
        }

        $updates = $data;

        if (array_key_exists('app_id', $updates) && ! $this->isOwnedClub($updates['app_id'], $userId)) {
            return response()->json([
                'message' => 'You are not allowed to move this event to that club.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (empty($updates)) {
            return response()->json([
                'message' => 'No valid fields were provided for update.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = DB::selectOne(
                'SELECT sp_update_event(?, ?, ?, ?, ?, ?, ?, ?, ?) AS updated',
                [
                    $id,
                    $updates['title'] ?? null,
                    $updates['organizer'] ?? null,
                    $this->toDatabaseDate($updates['start_date'] ?? null),
                    $updates['description'] ?? null,
                    $updates['location'] ?? null,
                    $updates['url'] ?? null,
                    $updates['img'] ?? null,
                    $updates['app_id'] ?? null,
                ]
            );

            if (array_key_exists('start_date', $updates) && $updates['start_date'] === null) {
                DB::table('events')->where('id', $id)->update(['start_date' => null, 'updated_at' => now()]);
            }
        } catch (QueryException $exception) {
            return $this->storedProcedureErrorResponse($exception);
        }

        if (! $this->postgresBool($result->updated ?? false)) {
            $this->logAction('Partial event update returned no affected row', [
                'user_id' => $userId,
                'event_id' => $id,
            ], 'warning');

            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        $this->logAction('Event partially updated', [
            'user_id' => $userId,
            'event_id' => $id,
            'updated_fields' => array_keys($updates),
        ]);

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
    public function destroy(Request $request, string $id)
    {
        $userId = $this->authenticatedUserId($request);
        $event = DB::table('events')->where('id', $id)->first();

        if (! $event) {
            $this->logAction('Event delete requested for missing event', [
                'user_id' => $userId,
                'event_id' => $id,
            ], 'warning');

            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        if (! $this->isOwnedEvent($id, $userId)) {
            $this->logAction('Event delete rejected because the event is not owned by the authenticated user', [
                'user_id' => $userId,
                'event_id' => $id,
            ], 'warning');

            return response()->json([
                'message' => 'You are not allowed to delete this event.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $result = DB::selectOne('SELECT sp_delete_event(?) AS deleted', [$id]);
        } catch (QueryException $exception) {
            return $this->storedProcedureErrorResponse($exception);
        }

        if (! $this->postgresBool($result->deleted ?? false)) {
            $this->logAction('Event delete returned no affected row', [
                'user_id' => $userId,
                'event_id' => $id,
            ], 'warning');

            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        $this->logAction('Event deleted', [
            'user_id' => $userId,
            'event_id' => $id,
        ]);

        return response()->json([
            'message' => 'Event deleted successfully.',
        ], Response::HTTP_OK);
    }

    private function applySearchFilter(&$query, ?string $search): void
    {
        $term = trim((string) $search);

        if ($term === '') {
            return;
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\', '%', '_'], Str::lower($term));
        $pattern = "%{$escaped}%";

        $query = $query->where(function ($subQuery) use ($pattern) {
            $subQuery->whereRaw("LOWER(COALESCE(name, '')) LIKE ?", [$pattern])
                ->orWhereRaw("LOWER(COALESCE(organizer, '')) LIKE ?", [$pattern])
                ->orWhereRaw("LOWER(COALESCE(description, '')) LIKE ?", [$pattern])
                ->orWhereRaw("LOWER(COALESCE(location, '')) LIKE ?", [$pattern])
                ->orWhereRaw("LOWER(COALESCE(url, '')) LIKE ?", [$pattern])
                ->orWhereRaw("LOWER(COALESCE(app_name, '')) LIKE ?", [$pattern]);
        });
    }

    private function authenticatedUserId(Request $request): int
    {
        return (int) $request->user()->id;
    }

    private function isOwnedClub(string $clubId, int $userId): bool
    {
        return DB::table('clubs')
            ->where('id', $clubId)
            ->where('owner_user_id', $userId)
            ->exists();
    }

    private function isOwnedEvent(string $eventId, int $userId): bool
    {
        return DB::table('events as e')
            ->join('clubs as c', 'c.id', '=', 'e.app_id')
            ->where('e.id', $eventId)
            ->where('c.owner_user_id', $userId)
            ->exists();
    }

    private function resolveDefaultOwnedClubId(int $userId): ?string
    {
        $clubId = DB::table('clubs')
            ->where('owner_user_id', $userId)
            ->orderBy('id')
            ->value('id');

        return $clubId ? (string) $clubId : null;
    }

    private function toDatabaseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private function postgresBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['t', 'true', '1'], true);
    }
}
