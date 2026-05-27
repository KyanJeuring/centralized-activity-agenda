<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreScraperStagingRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Throwable;

class ScraperController extends Controller
{
    public function createStaging(StoreScraperStagingRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $club = DB::table('clubs')
            ->where('id', $data['app_id'])
            ->first();

        if (! $club) {
            return response()->json(['message' => 'Club not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($club->owner_user_id !== $user->id) {
            return response()->json(['message' => 'Not authorized for this club.'], Response::HTTP_FORBIDDEN);
        }

        if ($club->type !== 'scraped') {
            return response()->json(['message' => 'Club is not configured for scraper staging.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = DB::selectOne(
                'SELECT sp_create_staging(?, ?) AS staging_id',
                [
                    $data['app_id'],
                    json_encode($data['raw_data']),
                ]
            );
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Failed to create staging record.',
                'error' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'staging_id' => $result->staging_id,
        ], Response::HTTP_CREATED);
    }
}