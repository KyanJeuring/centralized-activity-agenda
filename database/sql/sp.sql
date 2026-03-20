-- Stored procedures / functions for centralized-activity-agenda
-- Notes:
--  - Run after database/sql/tables.sql so the base tables/types exist.
--  - This file intentionally does NOT create/replace any views.
--  - Read procedures query base tables directly so view changes won’t break SPs.

-- =============================================================================
-- Optional pre-reqs
-- =============================================================================

-- For UUID generation (recommended)
-- If you prefer to avoid extensions, you can pass explicit UUIDs to create functions.
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- =============================================================================
-- Legacy cleanup (safe to keep; helps when iterating)
-- =============================================================================

DROP FUNCTION IF EXISTS sp_read_clubs(club_type_enum);
DROP FUNCTION IF EXISTS sp_read_events(UUID);
DROP FUNCTION IF EXISTS sp_read_event_details(UUID);
DROP FUNCTION IF EXISTS sp_read_event_by_external_id(UUID, TEXT);
DROP FUNCTION IF EXISTS sp_read_staging(UUID, INT);

-- =============================================================================
-- Helpers
-- =============================================================================

-- Function: sp_generate_uuid()
-- Purpose: Generates a UUID for inserts.
-- Returns: UUID
-- Notes:
--  - Requires pgcrypto (gen_random_uuid()).
CREATE OR REPLACE FUNCTION sp_generate_uuid()
RETURNS UUID AS $$
BEGIN
    RETURN gen_random_uuid();
END;
$$ LANGUAGE plpgsql;

-- Function: sp_next_bigint_id(p_table REGCLASS, p_id_column TEXT)
-- Purpose: Generates a new BIGINT id for tables that don't have IDENTITY/SERIAL.
-- Returns: BIGINT
-- Notes:
--  - Uses a transaction-scoped advisory lock to reduce race conditions.
CREATE OR REPLACE FUNCTION sp_next_bigint_id(
    p_table REGCLASS,
    p_id_column TEXT
) RETURNS BIGINT AS $$
DECLARE
    v_next BIGINT;
    v_lock_key BIGINT;
BEGIN
    v_lock_key := hashtext(p_table::TEXT || '.' || p_id_column);
    PERFORM pg_advisory_xact_lock(v_lock_key);

    EXECUTE format('SELECT COALESCE(MAX(%I), 0) + 1 FROM %s', p_id_column, p_table)
    INTO v_next;

    RETURN v_next;
END;
$$ LANGUAGE plpgsql;

-- =============================================================================
-- Clubs
-- =============================================================================

-- Function: sp_read_clubs(p_type club_type_enum DEFAULT NULL)
-- Purpose: Reads clubs (optionally filtered by type).
-- Returns: SETOF clubs
CREATE OR REPLACE FUNCTION sp_read_clubs(
    p_type club_type_enum DEFAULT NULL
) RETURNS SETOF clubs AS $$
BEGIN
    RETURN QUERY
    SELECT c.*
    FROM clubs c
    WHERE p_type IS NULL OR c.type = p_type
    ORDER BY c.name;
END;
$$ LANGUAGE plpgsql;

-- Function: sp_create_club(p_name, p_source, p_type, ...)
-- Purpose: Creates a new club/app.
-- Returns: created club UUID
-- Notes:
--  - Enforces case-insensitive uniqueness on (name, source) at SP-level.
CREATE OR REPLACE FUNCTION sp_create_club(
    p_name VARCHAR,
    p_source TEXT,
    p_type club_type_enum,
    p_issued_key TEXT DEFAULT NULL,
    p_received_key TEXT DEFAULT NULL,
    p_id UUID DEFAULT NULL
) RETURNS UUID AS $$
DECLARE
    v_id UUID;
BEGIN
    v_id := COALESCE(p_id, sp_generate_uuid());

    IF EXISTS (
        SELECT 1
        FROM clubs
        WHERE LOWER(name) = LOWER(p_name)
          AND source = p_source
    ) THEN
        RAISE EXCEPTION 'Club with name "%" and source "%" already exists', p_name, p_source;
    END IF;

    INSERT INTO clubs(id, name, source, issued_key, received_key, type)
    VALUES (v_id, p_name, p_source, p_issued_key, p_received_key, p_type);

    RETURN v_id;
END;
$$ LANGUAGE plpgsql;

-- Function: sp_update_club_keys(p_id, p_issued_key, p_received_key)
-- Purpose: Updates issued/received keys.
-- Returns: TRUE if updated, else FALSE
CREATE OR REPLACE FUNCTION sp_update_club_keys(
    p_id UUID,
    p_issued_key TEXT DEFAULT NULL,
    p_received_key TEXT DEFAULT NULL
) RETURNS BOOLEAN AS $$
BEGIN
    UPDATE clubs
    SET
        issued_key = COALESCE(p_issued_key, issued_key),
        received_key = COALESCE(p_received_key, received_key)
    WHERE id = p_id;

    RETURN FOUND;
END;
$$ LANGUAGE plpgsql;

-- Function: sp_delete_club(p_id)
-- Purpose: Deletes a club.
-- Returns: TRUE if deleted, else FALSE
-- Notes:
--  - Will fail if referenced by events/ext_int_ids/staging due to FK constraints.
CREATE OR REPLACE FUNCTION sp_delete_club(
    p_id UUID
) RETURNS BOOLEAN AS $$
BEGIN
    DELETE FROM clubs WHERE id = p_id;
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql;

-- =============================================================================
-- Events
-- =============================================================================

-- Function: sp_read_events(p_app_id UUID DEFAULT NULL)
-- Purpose: Reads events (optionally filtered by app/club).
-- Returns: event rows joined with app/club metadata
CREATE OR REPLACE FUNCTION sp_read_events(
    p_app_id UUID DEFAULT NULL
) RETURNS TABLE (
    id UUID,
    name TEXT,
    organizer TEXT,
    start_date DATE,
    description TEXT,
    location TEXT,
    url TEXT,
    app_id UUID,
    app_name VARCHAR(255),
    app_source TEXT,
    app_type club_type_enum,
    img TEXT,
    created_at TIMESTAMP(0) WITH TIME ZONE,
    updated_at TIMESTAMP(0) WITH TIME ZONE
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        e.id,
        e.name,
        e.organizer,
        e.start_date,
        e.description,
        e.location,
        e.url,
        e.app_id,
        c.name AS app_name,
        c.source AS app_source,
        c.type AS app_type,
        e.img,
        e.created_at,
        e.updated_at
    FROM events e
    JOIN clubs c ON c.id = e.app_id
    WHERE p_app_id IS NULL OR e.app_id = p_app_id
    ORDER BY e.start_date NULLS LAST, e.created_at DESC;
END;
$$ LANGUAGE plpgsql;

-- Function: sp_read_event_details(p_event_id UUID)
-- Purpose: Reads a single event with tag list.
-- Returns: 1 row (or none) with tags as JSONB array: [{id, slug}, ...]
CREATE OR REPLACE FUNCTION sp_read_event_details(
    p_event_id UUID
) RETURNS TABLE (
    id UUID,
    name TEXT,
    organizer TEXT,
    start_date DATE,
    description TEXT,
    location TEXT,
    url TEXT,
    app_id UUID,
    app_name VARCHAR(255),
    app_source TEXT,
    app_type club_type_enum,
    img TEXT,
    created_at TIMESTAMP(0) WITH TIME ZONE,
    updated_at TIMESTAMP(0) WITH TIME ZONE,
    tags JSONB
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        e.id,
        e.name,
        e.organizer,
        e.start_date,
        e.description,
        e.location,
        e.url,
        e.app_id,
        c.name AS app_name,
        c.source AS app_source,
        c.type AS app_type,
        e.img,
        e.created_at,
        e.updated_at,
        COALESCE(
            jsonb_agg(jsonb_build_object('id', t.id, 'slug', t.slug) ORDER BY t.slug)
                FILTER (WHERE t.id IS NOT NULL),
            '[]'::jsonb
        ) AS tags
    FROM events e
    JOIN clubs c ON c.id = e.app_id
    LEFT JOIN event_tag et ON et.event_id = e.id
    LEFT JOIN tags t ON t.id = et.tag_id
    WHERE e.id = p_event_id
    GROUP BY e.id, c.id;
END;
$$ LANGUAGE plpgsql;

-- Function: sp_create_event(...)
-- Purpose: Creates a new event for an app/club.
-- Returns: created event UUID
-- Notes:
--  - Prevents duplicate URL per app at SP-level.
CREATE OR REPLACE FUNCTION sp_create_event(
    p_app_id UUID,
    p_name TEXT,
    p_organizer TEXT,
    p_description TEXT,
    p_url TEXT,
    p_start_date DATE DEFAULT NULL,
    p_location TEXT DEFAULT NULL,
    p_img TEXT DEFAULT NULL,
    p_id UUID DEFAULT NULL
) RETURNS UUID AS $$
DECLARE
    v_id UUID;
BEGIN
    v_id := COALESCE(p_id, sp_generate_uuid());

    IF NOT EXISTS (SELECT 1 FROM clubs WHERE id = p_app_id) THEN
        RAISE EXCEPTION 'App/club with id % does not exist', p_app_id;
    END IF;

    IF EXISTS (
        SELECT 1
        FROM events
        WHERE app_id = p_app_id
          AND url = p_url
    ) THEN
        RAISE EXCEPTION 'Event with url "%" already exists for app %', p_url, p_app_id;
    END IF;

    INSERT INTO events(
        id, name, organizer, start_date, description, location, url, app_id, img,
        created_at, updated_at
    ) VALUES (
        v_id, p_name, p_organizer, p_start_date, p_description, p_location, p_url, p_app_id, p_img,
        NOW(), NOW()
    );

    RETURN v_id;
END;
$$ LANGUAGE plpgsql;

-- Function: sp_update_event(...)
-- Purpose: Updates an event (NULL means “leave unchanged”).
-- Returns: TRUE if updated, else FALSE
CREATE OR REPLACE FUNCTION sp_update_event(
    p_event_id UUID,
    p_name TEXT DEFAULT NULL,
    p_organizer TEXT DEFAULT NULL,
    p_start_date DATE DEFAULT NULL,
    p_description TEXT DEFAULT NULL,
    p_location TEXT DEFAULT NULL,
    p_url TEXT DEFAULT NULL,
    p_img TEXT DEFAULT NULL
) RETURNS BOOLEAN AS $$
BEGIN
    UPDATE events
    SET
        name = COALESCE(p_name, name),
        organizer = COALESCE(p_organizer, organizer),
        start_date = COALESCE(p_start_date, start_date),
        description = COALESCE(p_description, description),
        location = COALESCE(p_location, location),
        url = COALESCE(p_url, url),
        img = COALESCE(p_img, img),
        updated_at = NOW()
    WHERE id = p_event_id;

    RETURN FOUND;
END;
$$ LANGUAGE plpgsql;

-- Function: sp_delete_event(p_event_id UUID)
-- Purpose: Deletes an event and its dependent relations.
-- Returns: TRUE if deleted, else FALSE
CREATE OR REPLACE FUNCTION sp_delete_event(
    p_event_id UUID
) RETURNS BOOLEAN AS $$
BEGIN
    DELETE FROM event_tag WHERE event_id = p_event_id;
    DELETE FROM ext_int_ids WHERE event_id = p_event_id;
    DELETE FROM events WHERE id = p_event_id;

    RETURN FOUND;
END;
$$ LANGUAGE plpgsql;

-- =============================================================================
-- External IDs
-- =============================================================================

-- Function: sp_upsert_external_id(p_event_id, p_app_id, p_external_id)
-- Purpose: Links an internal event_id to a per-app external_id.
-- Returns: ext_int_ids.id (UUID)
-- Notes:
--  - If (app_id, external_id) exists, updates event_id and returns the existing row id.
CREATE OR REPLACE FUNCTION sp_upsert_external_id(
    p_event_id UUID,
    p_app_id UUID,
    p_external_id TEXT
) RETURNS UUID AS $$
DECLARE
    v_id UUID;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM events WHERE id = p_event_id) THEN
        RAISE EXCEPTION 'Event % does not exist', p_event_id;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM clubs WHERE id = p_app_id) THEN
        RAISE EXCEPTION 'App/club % does not exist', p_app_id;
    END IF;

    SELECT id INTO v_id
    FROM ext_int_ids
    WHERE app_id = p_app_id AND external_id = p_external_id
    ORDER BY created_at DESC
    LIMIT 1;

    IF v_id IS NOT NULL THEN
        UPDATE ext_int_ids
        SET event_id = p_event_id
        WHERE id = v_id;
        RETURN v_id;
    END IF;

    INSERT INTO ext_int_ids(id, event_id, app_id, external_id, created_at)
    VALUES (sp_generate_uuid(), p_event_id, p_app_id, p_external_id, NOW())
    RETURNING id INTO v_id;

    RETURN v_id;
END;
$$ LANGUAGE plpgsql;

-- Function: sp_read_event_by_external_id(p_app_id, p_external_id)
-- Purpose: Resolves a single event via (app_id, external_id).
-- Returns: event details row (same shape as sp_read_event_details)
CREATE OR REPLACE FUNCTION sp_read_event_by_external_id(
    p_app_id UUID,
    p_external_id TEXT
) RETURNS TABLE (
    id UUID,
    name TEXT,
    organizer TEXT,
    start_date DATE,
    description TEXT,
    location TEXT,
    url TEXT,
    app_id UUID,
    app_name VARCHAR(255),
    app_source TEXT,
    app_type club_type_enum,
    img TEXT,
    created_at TIMESTAMP(0) WITH TIME ZONE,
    updated_at TIMESTAMP(0) WITH TIME ZONE,
    tags JSONB
) AS $$
DECLARE
    v_event_id UUID;
BEGIN
    SELECT x.event_id INTO v_event_id
    FROM ext_int_ids x
    WHERE x.app_id = p_app_id
      AND x.external_id = p_external_id
    ORDER BY x.created_at DESC
    LIMIT 1;

    IF v_event_id IS NULL THEN
        RETURN;
    END IF;

    RETURN QUERY
    SELECT * FROM sp_read_event_details(v_event_id);
END;
$$ LANGUAGE plpgsql;

-- =============================================================================
-- Staging
-- =============================================================================

-- Function: sp_create_staging(p_app_id, p_raw_data)
-- Purpose: Inserts a staging row for ingestion.
-- Returns: created staging UUID
CREATE OR REPLACE FUNCTION sp_create_staging(
    p_app_id UUID,
    p_raw_data JSONB
) RETURNS UUID AS $$
DECLARE
    v_id UUID;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM clubs WHERE id = p_app_id) THEN
        RAISE EXCEPTION 'App/club with id % does not exist', p_app_id;
    END IF;

    v_id := sp_generate_uuid();
    INSERT INTO staging(id, raw_data, app_id, created_at)
    VALUES (v_id, p_raw_data, p_app_id, NOW());

    RETURN v_id;
END;
$$ LANGUAGE plpgsql;

-- Function: sp_read_staging(p_app_id, p_limit)
-- Purpose: Reads staging rows for an app.
-- Returns: staging rows joined with app name
-- Notes:
--  - p_limit is clamped to avoid accidental huge reads.
CREATE OR REPLACE FUNCTION sp_read_staging(
    p_app_id UUID,
    p_limit INT DEFAULT 100
) RETURNS TABLE(
    id UUID,
    raw_data JSONB,
    app_id UUID,
    app_name VARCHAR(255),
    created_at TIMESTAMP(0) WITH TIME ZONE
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        s.id,
        s.raw_data,
        s.app_id,
        c.name AS app_name,
        s.created_at
    FROM staging s
    JOIN clubs c ON c.id = s.app_id
    WHERE s.app_id = p_app_id
    ORDER BY s.created_at DESC
    LIMIT GREATEST(0, LEAST(p_limit, 1000));
END;
$$ LANGUAGE plpgsql;

-- Function: sp_delete_staging(p_staging_id)
-- Purpose: Deletes one staging row.
-- Returns: TRUE if deleted, else FALSE
CREATE OR REPLACE FUNCTION sp_delete_staging(
    p_staging_id UUID
) RETURNS BOOLEAN AS $$
BEGIN
    DELETE FROM staging WHERE id = p_staging_id;
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql;

-- =============================================================================
-- Tags
-- =============================================================================

-- Function: sp_read_tags()
-- Purpose: Reads all tags.
-- Returns: SETOF tags
CREATE OR REPLACE FUNCTION sp_read_tags()
RETURNS SETOF tags AS $$
BEGIN
    RETURN QUERY
    SELECT * FROM tags ORDER BY slug;
END;
$$ LANGUAGE plpgsql;

-- Function: sp_create_tag(p_slug)
-- Purpose: Creates a tag (case-insensitive uniqueness by slug).
-- Returns: tag id
-- Notes:
--  - tables.sql does not define a UNIQUE constraint on tags.slug.
--  - This function uses an advisory lock to reduce duplicates under concurrency.
CREATE OR REPLACE FUNCTION sp_create_tag(
    p_slug TEXT
) RETURNS BIGINT AS $$
DECLARE
    v_existing_id BIGINT;
    v_id BIGINT;
    v_slug TEXT;
BEGIN
    v_slug := TRIM(p_slug);
    IF v_slug IS NULL OR LENGTH(v_slug) = 0 THEN
        RAISE EXCEPTION 'Tag slug cannot be empty';
    END IF;

    PERFORM pg_advisory_xact_lock(hashtext('tags:' || LOWER(v_slug)));

    SELECT id INTO v_existing_id
    FROM tags
    WHERE LOWER(slug) = LOWER(v_slug)
    LIMIT 1;

    IF v_existing_id IS NOT NULL THEN
        RETURN v_existing_id;
    END IF;

    v_id := sp_next_bigint_id('tags'::REGCLASS, 'id');
    INSERT INTO tags(id, slug)
    VALUES (v_id, v_slug);

    RETURN v_id;
END;
$$ LANGUAGE plpgsql;

-- Function: sp_tag_event(p_event_id, p_tag_slug)
-- Purpose: Attaches a tag to an event (creates the tag if missing).
-- Returns: TRUE if tag is present afterwards
CREATE OR REPLACE FUNCTION sp_tag_event(
    p_event_id UUID,
    p_tag_slug TEXT
) RETURNS BOOLEAN AS $$
DECLARE
    v_tag_id BIGINT;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM events WHERE id = p_event_id) THEN
        RAISE EXCEPTION 'Event % does not exist', p_event_id;
    END IF;

    v_tag_id := sp_create_tag(p_tag_slug);

    IF EXISTS (
        SELECT 1
        FROM event_tag
        WHERE event_id = p_event_id
          AND tag_id = v_tag_id
    ) THEN
        RETURN TRUE;
    END IF;

    INSERT INTO event_tag(id, event_id, tag_id)
    VALUES (sp_next_bigint_id('event_tag'::REGCLASS, 'id'), p_event_id, v_tag_id);

    RETURN TRUE;
END;
$$ LANGUAGE plpgsql;

-- Function: sp_untag_event(p_event_id, p_tag_slug)
-- Purpose: Removes a tag from an event.
-- Returns: TRUE if removed, else FALSE
CREATE OR REPLACE FUNCTION sp_untag_event(
    p_event_id UUID,
    p_tag_slug TEXT
) RETURNS BOOLEAN AS $$
DECLARE
    v_tag_id BIGINT;
BEGIN
    SELECT id INTO v_tag_id
    FROM tags
    WHERE LOWER(slug) = LOWER(TRIM(p_tag_slug))
    LIMIT 1;

    IF v_tag_id IS NULL THEN
        RETURN FALSE;
    END IF;

    DELETE FROM event_tag
    WHERE event_id = p_event_id
      AND tag_id = v_tag_id;

    RETURN FOUND;
END;
$$ LANGUAGE plpgsql;
