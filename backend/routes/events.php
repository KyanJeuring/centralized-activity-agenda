<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
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
        $events = DB::table('vw_events_all')
            ->orderByRaw('start_date IS NULL, start_date ASC')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($events, Response::HTTP_OK);
    });

    Route::get('events/upcoming', function () {
        $events = DB::table('vw_events_upcoming')
            ->orderBy('start_date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($events, Response::HTTP_OK);
    });

    Route::get('events/past', function () {
        $events = DB::table('vw_events_past')
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($events, Response::HTTP_OK);
    });

    Route::get('events/{id}', function ($id) {
        $event = DB::table('vw_event_details')
            ->where('id', $id)
            ->first();

        if (! $event) {
            return response()->json([
                'message' => "Event with ID {$id} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($event, Response::HTTP_OK);
    })->whereUuid('id');

    Route::post('events', function (Request $request) {
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
    });

    Route::put('events/{id}', function (Request $request, $id) {
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
    })->whereUuid('id');

    Route::patch('events/{id}/cancel', function ($id) {
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
    })->whereUuid('id');

    Route::patch('events/{id}', function (Request $request, $id) {
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
    })->whereUuid('id');

    Route::delete('events/{id}', function ($id) {
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
    })->whereUuid('id');
});
