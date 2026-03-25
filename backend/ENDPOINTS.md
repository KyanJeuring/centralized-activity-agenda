# Events API Documentation

This document describes the REST API endpoints used to manage events.

Base path:

/api/v1/events

---

# Summary

| Method | Endpoint                   | Description                       |
| ------ | -------------------------- | --------------------------------- |
| GET    | /api/v1/events             | Get upcoming events               |
| GET    | /api/v1/events/all         | Get all events                    |
| GET    | /api/v1/events/past        | Get past events                   |
| GET    | /api/v1/events/{id}        | Get a specific event              |
| POST   | /api/v1/events             | Create a new event                |
| PUT    | /api/v1/events/{id}        | Update a specific event           |
| PATCH  | /api/v1/events/{id}        | Partially update a specific event |
| PATCH  | /api/v1/events/{id}/cancel | Cancel a specific event           |
| DELETE | /api/v1/events/{id}        | Delete a specific event           |

---

## Search Parameter

Search is implemented as an optional query parameter on list endpoints:

- `GET /api/v1/events?search={term}`
- `GET /api/v1/events/all?search={term}`
- `GET /api/v1/events/past?search={term}`

Behavior:

- Case-insensitive matching.
- Matches any of: name, organizer, description, location, url, app_name.
- Empty or missing search returns unfiltered results.
- %, _, and ! are treated as literal characters (escaped), not SQL wildcards.

Example:

```http
GET /api/v1/events?search=hackathon
```

---

## Response Fields

Event responses include:

- id
- name
- organizer
- start_date
- description
- location
- url
- app_id
- app_name
- app_source
- app_type
- img
- is_cancelled
- created_at
- updated_at
- tags (only on detail response)

---

## GET Endpoints

### Get all events
GET /api/v1/events/all

Returns all events.

### Get a specific event
GET /api/v1/events/{id}

Returns one specific event by ID.

### Get upcoming events
GET /api/v1/events

Returns events with start_date today or later.

### Get past events
GET /api/v1/events/past

Returns all past events.

Note:

- List endpoints return paginated results.

---

## POST Endpoint

### Create event
POST /api/v1/events

Creates a new event.

Rules:

- Requires authenticated user.
- Event is created in the authenticated user's owned club.
- id and app_id are not accepted in request body.
- Either name or title is required.

Example request body:
```json
{
  "name": "Open Day",
  "organizer": "Student Affairs",
  "description": "Campus open day for new students",
  "url": "https://example.edu/open-day",
  "start_date": "2026-03-20",
  "location": "Main Building",
  "img": "https://example.edu/open-day.jpg"
}
```

---

## PUT Endpoint

### Update a specific event
PUT /api/v1/events/{id}

Updates the full event.

Rules:

- Requires authenticated user.
- Caller must own the event.
- app_id is not accepted in request body.
- PUT keeps the event in its current app/club.
- Either name or title is required.

Example request body:
```json
{
  "title": "Updated Event",
  "organizer": "Student Affairs",
  "description": "Updated description",
  "url": "https://example.edu/open-day-updated",
  "start_date": "2026-03-21",
  "location": "Conference Hall",
  "img": "https://example.edu/open-day-updated.jpg"
}
```

---

## PATCH Endpoints

### Partially update a specific event
PATCH /api/v1/events/{id}

Updates only specific fields of an event.

Example request body:
```json
{
  "title": "Updated title"
}
```

### Cancel a specific event
PATCH /api/v1/events/{id}/cancel

Marks an event as cancelled by setting is_cancelled to true.

Rules:

- Requires authenticated user.
- Caller must own the event.

---

## DELETE Endpoint

### Delete a specific event
DELETE /api/v1/events/{id}

Deletes the event with the given ID.

Rules:

- Requires authenticated user.
- Caller must own the event.
