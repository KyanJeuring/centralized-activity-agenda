<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        $proceduresPath = base_path('database_sql/sp.sql');

        if (! File::exists($proceduresPath)) {
            throw new RuntimeException("SQL file not found: {$proceduresPath}");
        }

        DB::unprepared(File::get($proceduresPath));
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP FUNCTION IF EXISTS sp_untag_event(UUID, BIGINT);
            DROP FUNCTION IF EXISTS sp_tag_event(UUID, BIGINT);
            DROP FUNCTION IF EXISTS sp_create_tag(TEXT, BIGINT);
            DROP FUNCTION IF EXISTS sp_read_tags();
            DROP FUNCTION IF EXISTS sp_delete_staging(UUID);
            DROP FUNCTION IF EXISTS sp_read_staging(UUID, INT);
            DROP FUNCTION IF EXISTS sp_create_staging(UUID, JSONB, UUID);
            DROP FUNCTION IF EXISTS sp_read_event_by_external_id(UUID, TEXT);
            DROP FUNCTION IF EXISTS sp_upsert_external_id(UUID, UUID, TEXT, UUID);
            DROP FUNCTION IF EXISTS sp_delete_event(UUID);
            DROP FUNCTION IF EXISTS sp_update_event(UUID, TEXT, TEXT, DATE, TEXT, TEXT, TEXT, TEXT);
            DROP FUNCTION IF EXISTS sp_create_event(UUID, TEXT, TEXT, TEXT, TEXT, DATE, TEXT, TEXT, UUID);
            DROP FUNCTION IF EXISTS sp_read_event_details(UUID);
            DROP FUNCTION IF EXISTS sp_read_events(UUID);
            DROP FUNCTION IF EXISTS sp_delete_club(UUID);
            DROP FUNCTION IF EXISTS sp_update_club_keys(UUID, TEXT, TEXT);
            DROP FUNCTION IF EXISTS sp_create_club(VARCHAR, TEXT, club_type_enum, TEXT, TEXT, UUID);
            DROP FUNCTION IF EXISTS sp_read_clubs(club_type_enum);
            DROP FUNCTION IF EXISTS sp_next_bigint_id(REGCLASS, TEXT);
            DROP FUNCTION IF EXISTS sp_generate_uuid();
        SQL);
    }
};
