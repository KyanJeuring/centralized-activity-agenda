-- Enums

DO $$
BEGIN
    CREATE TYPE club_type_enum AS ENUM ('remote', 'local', 'scraped');
EXCEPTION
    WHEN duplicate_object THEN null;
END
$$;

-- Tables

CREATE TABLE IF NOT EXISTS 'clubs' (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL, 
    source TEXT NOT NULL,
    issued_key TEXT,
    received_key TEXT,
    type club_type_enum NOT NULL
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