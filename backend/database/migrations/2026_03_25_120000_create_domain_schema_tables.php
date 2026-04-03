<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            DO $$
            BEGIN
                CREATE TYPE club_type_enum AS ENUM ('remote', 'local', 'scraped');
            EXCEPTION
                WHEN duplicate_object THEN null;
            END
            $$;

            CREATE TABLE IF NOT EXISTS clubs (
                id UUID PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                source TEXT NOT NULL,
                issued_key TEXT,
                received_key TEXT,
                type club_type_enum NOT NULL,
                owner_user_id BIGINT NOT NULL,
                CONSTRAINT clubs_owner_user_id_foreign
                    FOREIGN KEY (owner_user_id) REFERENCES users(id)
            );

            CREATE TABLE IF NOT EXISTS events (
                id UUID PRIMARY KEY,
                name TEXT NOT NULL,
                organizer TEXT NOT NULL,
                start_date DATE NULL,
                description TEXT NOT NULL,
                location TEXT NULL,
                url TEXT NOT NULL,
                app_id UUID NOT NULL,
                img TEXT NULL,
                is_cancelled BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
                CONSTRAINT events_app_id_foreign
                    FOREIGN KEY (app_id) REFERENCES clubs(id)
            );

            CREATE TABLE IF NOT EXISTS ext_int_ids (
                id UUID PRIMARY KEY,
                event_id UUID NOT NULL,
                app_id UUID NOT NULL,
                external_id TEXT NOT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
                CONSTRAINT ext_int_ids_event_id_foreign
                    FOREIGN KEY (event_id) REFERENCES events(id),
                CONSTRAINT ext_int_ids_app_id_foreign
                    FOREIGN KEY (app_id) REFERENCES clubs(id)
            );

            CREATE TABLE IF NOT EXISTS staging (
                id UUID PRIMARY KEY,
                raw_data JSONB NOT NULL,
                app_id UUID NOT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
                CONSTRAINT staging_app_id_foreign
                    FOREIGN KEY (app_id) REFERENCES clubs(id)
            );

            CREATE TABLE IF NOT EXISTS tags (
                id BIGINT PRIMARY KEY,
                slug TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS event_tag (
                id BIGINT PRIMARY KEY,
                event_id UUID NOT NULL,
                tag_id BIGINT NOT NULL,
                CONSTRAINT event_tag_event_id_foreign
                    FOREIGN KEY (event_id) REFERENCES events(id),
                CONSTRAINT event_tag_tag_id_foreign
                    FOREIGN KEY (tag_id) REFERENCES tags(id)
            );

            CREATE INDEX IF NOT EXISTS events_app_id_idx ON events(app_id);
            CREATE INDEX IF NOT EXISTS clubs_owner_user_id_idx ON clubs(owner_user_id);
            CREATE INDEX IF NOT EXISTS ext_int_ids_event_id_idx ON ext_int_ids(event_id);
            CREATE INDEX IF NOT EXISTS ext_int_ids_app_id_idx ON ext_int_ids(app_id);
            CREATE INDEX IF NOT EXISTS staging_app_id_idx ON staging(app_id);
            CREATE INDEX IF NOT EXISTS event_tag_event_id_idx ON event_tag(event_id);
            CREATE INDEX IF NOT EXISTS event_tag_tag_id_idx ON event_tag(tag_id);
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TABLE IF EXISTS event_tag;
            DROP TABLE IF EXISTS ext_int_ids;
            DROP TABLE IF EXISTS staging;
            DROP TABLE IF EXISTS events;
            DROP TABLE IF EXISTS tags;
            DROP TABLE IF EXISTS clubs;
            DROP TYPE IF EXISTS club_type_enum;
        SQL);
    }
};
