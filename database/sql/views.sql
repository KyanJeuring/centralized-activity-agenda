-- Views for centralized-activity-agenda

-- =============================================================================
-- Legacy cleanup (safe to keep while iterating)
-- =============================================================================

DROP VIEW IF EXISTS vw_events_today;
DROP VIEW IF EXISTS vw_events_undated;
DROP VIEW IF EXISTS vw_events_upcoming;
DROP VIEW IF EXISTS vw_events_past;
DROP VIEW IF EXISTS vw_event_details;
DROP VIEW IF EXISTS vw_events_all;

-- =============================================================================
-- Canonical event feed
-- =============================================================================

-- View: vw_events_all
-- Purpose: canonical event projection enriched with app/club metadata.
-- Includes a computed date_status to simplify consumers.
CREATE VIEW vw_events_all AS
SELECT
	event.id,
	event.name,
	event.organizer,
	event.start_date,
	event.description,
	event.location,
	event.url,
	event.app_id,
	COALESCE(u.name, c.name) AS app_name,
	c.source AS app_source,
	c.type AS app_type,
	c.owner_user_id,
	event.img,
	event.created_at,
	event.updated_at
FROM events event
JOIN clubs c ON c.id = event.app_id
LEFT JOIN users u ON u.id = c.owner_user_id;

-- =============================================================================
-- Event timeline slices
-- =============================================================================

-- View: vw_events_upcoming
-- Purpose: events happening today or later.
CREATE VIEW vw_events_upcoming AS
SELECT *
FROM vw_events_all
WHERE start_date >= CURRENT_DATE
ORDER BY start_date ASC, created_at DESC;

-- View: vw_events_past
-- Purpose: events strictly before today.
CREATE VIEW vw_events_past AS
SELECT *
FROM vw_events_all
WHERE start_date < CURRENT_DATE
ORDER BY start_date DESC, created_at DESC;

-- View: vw_events_today
-- Purpose: convenience view for today's events.
CREATE VIEW vw_events_today AS
SELECT *
FROM vw_events_all
WHERE start_date = CURRENT_DATE
ORDER BY created_at DESC;

-- View: vw_events_undated
-- Purpose: events without a start date.
CREATE VIEW vw_events_undated AS
SELECT *
FROM vw_events_all
WHERE start_date IS NULL
ORDER BY created_at DESC;

-- =============================================================================
-- Event details with tags
-- =============================================================================

-- View: vw_event_details
-- Purpose: one row per event with tags as JSONB array.
CREATE VIEW vw_event_details AS
SELECT
	event.id,
	event.name,
	event.organizer,
	event.start_date,
	event.description,
	event.location,
	event.url,
	event.app_id,
	COALESCE(u.name, c.name) AS app_name,
	c.source AS app_source,
	c.type AS app_type,
	c.owner_user_id,
	event.img,
	event.created_at,
	event.updated_at,
	COALESCE(
		jsonb_agg(jsonb_build_object('id', t.id, 'slug', t.slug) ORDER BY t.slug)
			FILTER (WHERE t.id IS NOT NULL),
		'[]'::jsonb
	) AS tags
FROM events event
JOIN clubs c ON c.id = event.app_id
LEFT JOIN users u ON u.id = c.owner_user_id
LEFT JOIN event_tag et ON et.event_id = event.id
LEFT JOIN tags t ON t.id = et.tag_id
GROUP BY event.id, c.id, u.id;
