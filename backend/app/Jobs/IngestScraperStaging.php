<?php

namespace App\Jobs;

use App\Services\EventUrlResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class IngestScraperStaging implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    public function handle(): void
    {
        $rows = DB::table('staging')->orderBy('created_at')->limit(100)->get();

        if ($rows->isEmpty()) {
            return;
        }

        foreach ($rows as $row) {
            $this->processRow($row);
        }
    }

    private function processRow(object $row): void
    {
        try {
            $payload = json_decode($row->raw_data, true);

            if (! is_array($payload)) {
                DB::table('staging')->where('id', $row->id)->delete();

                return;
            }

            $events = $this->extractEvents($payload);

            if (empty($events)) {
                DB::table('staging')->where('id', $row->id)->delete();

                return;
            }

            DB::beginTransaction();

            foreach ($events as $event) {
                $externalId = $event['url'] ?? null;

                if (! $externalId) {
                    continue;
                }

                $existing = DB::table('ext_int_ids')
                    ->where('app_id', $row->app_id)
                    ->where('external_id', $externalId)
                    ->first();

                if ($existing) {
                    continue;
                }

                $startDate = $event['start_date'] ?? null;
                if ($startDate && isset($event['start_time'])) {
                    $startDate = sprintf('%sT%s:00.000Z', $startDate, $event['start_time']);
                }

                $resolvedUrl = app(EventUrlResolver::class)->resolve($event['url'] ?? '');

                $result = DB::selectOne(
                    'SELECT sp_create_event(?, ?, ?, ?, ?, ?, ?, ?) AS event_id',
                    [
                        $row->app_id,
                        $event['title'] ?? 'Untitled',
                        $event['organizer'] ?? 'Unknown',
                        $event['description'] ?? '',
                        $resolvedUrl,
                        $startDate,
                        $event['location'] ?? $event['source_page'] ?? null,
                        $event['img'] ?? null,
                    ]
                );

                if ($result && $result->event_id) {
                    DB::selectOne(
                        'SELECT sp_upsert_external_id(?, ?, ?) AS mapping_id',
                        [
                            $result->event_id,
                            $row->app_id,
                            $externalId,
                        ]
                    );
                }
            }

            DB::table('staging')->where('id', $row->id)->delete();
            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            \Log::error("Failed to ingest staging row {$row->id}: {$exception->getMessage()}");
        }
    }

    private function extractEvents(array $payload): array
    {
        $events = [];

        if (isset($payload['events']) && is_array($payload['events'])) {
            $events = $payload['events'];
        } elseif (isset($payload['result']['candidate_events']) && is_array($payload['result']['candidate_events'])) {
            $events = $payload['result']['candidate_events'];
        } elseif (array_is_list($payload)) {
            $events = $payload;
        }

        return $events;
    }
}
