-- Test data for centralized-activity-agenda
-- Run after database/sql/tables.sql
-- Optional: run database/sql/views.sql and database/sql/sp.sql after this
-- This script is idempotent via ON CONFLICT updates on primary keys.

BEGIN;

-- =============================================================================
-- Clubs
-- =============================================================================

INSERT INTO clubs (id, name, source, issued_key, received_key, type)
VALUES
    ('11111111-1111-1111-1111-111111111111', 'Main Campus', 'manual', 'issued-main', 'received-main', 'local'),
    ('22222222-2222-2222-2222-222222222222', 'Tech Community', 'remote-api', 'issued-tech', 'received-tech', 'remote'),
    ('33333333-3333-3333-3333-333333333333', 'Student Union Scraper', 'crawler', NULL, NULL, 'scraped')
ON CONFLICT (id) DO UPDATE
SET
    name = EXCLUDED.name,
    source = EXCLUDED.source,
    issued_key = EXCLUDED.issued_key,
    received_key = EXCLUDED.received_key,
    type = EXCLUDED.type;

-- =============================================================================
-- Tags
-- =============================================================================

INSERT INTO tags (id, slug)
VALUES
    (1, 'academic'),
    (2, 'career'),
    (3, 'social'),
    (4, 'sports'),
    (5, 'cancelled')
ON CONFLICT (id) DO UPDATE
SET slug = EXCLUDED.slug;

-- =============================================================================
-- Events
-- =============================================================================

INSERT INTO events (
    id, name, organizer, start_date, description, location, url, app_id, img, created_at, updated_at
)
VALUES
    (
        'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa1',
        'Spring Hackathon',
        'Computer Science Club',
        CURRENT_DATE + INTERVAL '10 days',
        '48-hour team hackathon focused on civic tech ideas.',
        'Innovation Lab',
        'https://events.example.com/spring-hackathon',
        '11111111-1111-1111-1111-111111111111',
        'https://images.example.com/hackathon.jpg',
        NOW() - INTERVAL '5 days',
        NOW() - INTERVAL '1 day'
    ),
    (
        'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa2',
        'Career Fair 2026',
        'Career Services',
        CURRENT_DATE + INTERVAL '20 days',
        'Meet recruiters from local and global companies.',
        'Main Hall',
        'https://events.example.com/career-fair-2026',
        '11111111-1111-1111-1111-111111111111',
        NULL,
        NOW() - INTERVAL '14 days',
        NOW() - INTERVAL '2 days'
    ),
    (
        'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa3',
        'Community Game Night',
        'Tech Community Team',
        CURRENT_DATE,
        'Board games and networking for developers and students.',
        'Student Center Room B',
        'https://events.example.com/game-night',
        '22222222-2222-2222-2222-222222222222',
        NULL,
        NOW() - INTERVAL '8 days',
        NOW() - INTERVAL '3 hours'
    ),
    (
        'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa4',
        'Winter Volleyball Tournament',
        'Athletics Department',
        CURRENT_DATE - INTERVAL '30 days',
        'Inter-faculty tournament finals.',
        'Sports Complex',
        'https://events.example.com/volleyball-tournament',
        '33333333-3333-3333-3333-333333333333',
        NULL,
        NOW() - INTERVAL '60 days',
        NOW() - INTERVAL '29 days'
    ),
    (
        'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa5',
        'Research Poster Session',
        'Graduate School Office',
        CURRENT_DATE - INTERVAL '5 days',
        'Student and faculty research presentations.',
        'Library Atrium',
        'https://events.example.com/poster-session',
        '11111111-1111-1111-1111-111111111111',
        NULL,
        NOW() - INTERVAL '18 days',
        NOW() - INTERVAL '4 days'
    ),
    (
        'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa6',
        'Open Source Contribution Workshop',
        'Tech Community Team',
        NULL,
        'Practical intro to contributing to open source projects.',
        'Online',
        'https://events.example.com/oss-workshop',
        '22222222-2222-2222-2222-222222222222',
        NULL,
        NOW() - INTERVAL '2 days',
        NOW() - INTERVAL '2 days'
    )
ON CONFLICT (id) DO UPDATE
SET
    name = EXCLUDED.name,
    organizer = EXCLUDED.organizer,
    start_date = EXCLUDED.start_date,
    description = EXCLUDED.description,
    location = EXCLUDED.location,
    url = EXCLUDED.url,
    app_id = EXCLUDED.app_id,
    img = EXCLUDED.img,
    updated_at = NOW();

-- =============================================================================
-- External to internal ID mappings
-- =============================================================================

INSERT INTO ext_int_ids (id, event_id, app_id, external_id, created_at)
VALUES
    ('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbb1', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa1', '11111111-1111-1111-1111-111111111111', 'main-evt-1001', NOW() - INTERVAL '5 days'),
    ('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbb2', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa3', '22222222-2222-2222-2222-222222222222', 'remote-evt-2042', NOW() - INTERVAL '8 days'),
    ('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbb3', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa4', '33333333-3333-3333-3333-333333333333', 'scraped-evt-311', NOW() - INTERVAL '60 days'),
    ('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbb4', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa6', '22222222-2222-2222-2222-222222222222', 'remote-evt-2099', NOW() - INTERVAL '2 days')
ON CONFLICT (id) DO UPDATE
SET
    event_id = EXCLUDED.event_id,
    app_id = EXCLUDED.app_id,
    external_id = EXCLUDED.external_id,
    created_at = EXCLUDED.created_at;

-- =============================================================================
-- Event-tag relationships
-- =============================================================================

INSERT INTO event_tag (id, event_id, tag_id)
VALUES
    (101, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa1', 1),
    (102, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa1', 2),
    (103, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa2', 2),
    (104, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa3', 3),
    (105, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa4', 4),
    (106, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa4', 5),
    (107, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa5', 1),
    (108, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa6', 1)
ON CONFLICT (id) DO UPDATE
SET
    event_id = EXCLUDED.event_id,
    tag_id = EXCLUDED.tag_id;

-- =============================================================================
-- Staging payloads
-- =============================================================================

INSERT INTO staging (id, raw_data, app_id, created_at)
VALUES
    (
        'cccccccc-cccc-cccc-cccc-ccccccccccc1',
        '{"source":"remote-api","external_id":"remote-evt-2100","name":"AI Meetup","start_date":"2026-05-04","location":"Online"}'::jsonb,
        '22222222-2222-2222-2222-222222222222',
        NOW() - INTERVAL '1 day'
    ),
    (
        'cccccccc-cccc-cccc-cccc-ccccccccccc2',
        '{"source":"crawler","external_id":"scraped-evt-322","name":"Chess Open","location":"Student Union"}'::jsonb,
        '33333333-3333-3333-3333-333333333333',
        NOW() - INTERVAL '12 hours'
    )
ON CONFLICT (id) DO UPDATE
SET
    raw_data = EXCLUDED.raw_data,
    app_id = EXCLUDED.app_id,
    created_at = EXCLUDED.created_at;

COMMIT;
