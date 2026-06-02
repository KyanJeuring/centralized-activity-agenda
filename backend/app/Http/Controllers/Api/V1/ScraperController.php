<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreScraperStagingRequest;
use App\Jobs\IngestScraperStaging;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Throwable;

class ScraperController extends Controller
{
    public function list()
    {
        $sites = DB::table('clubs')
            ->select(['id as app_id', 'source as url'])
            ->where('type', 'scraped')
            ->whereNotNull('source')
            ->orderBy('name')
            ->get();

        return response()->json([
            'sites' => $sites,
        ]);
    }

    public function createStaging(StoreScraperStagingRequest $request)
    {
        $data = $request->validated();
        $response = null;

        $club = DB::table('clubs')
            ->where('id', $data['app_id'])
            ->first();

        if (! $club) {
            $response = response()->json(['message' => 'Club not found.'], Response::HTTP_NOT_FOUND);
        } elseif ($club->type !== 'scraped') {
            $response = response()->json(['message' => 'Club is not configured for scraper staging.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        } else {
            try {
                $result = DB::selectOne(
                    'SELECT sp_create_staging(?, ?) AS staging_id',
                    [
                        $data['app_id'],
                        json_encode($data['raw_data']),
                    ]
                );

                $response = response()->json([
                    'staging_id' => $result->staging_id,
                ], Response::HTTP_CREATED);

                IngestScraperStaging::dispatchSync();
            } catch (Throwable $exception) {
                $response = response()->json([
                    'message' => 'Failed to create staging record.',
                    'error' => $exception->getMessage(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        return $response;
    }
}
