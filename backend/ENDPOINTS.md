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

## GET Endpoints

### Get all events
GET /api/v1/events/all

Returns all events.

### Get a specific event
GET /api/v1/events/{id}

Returns one specific event by ID.

### Get upcoming events
GET /api/v1/events

Returns all upcoming events.

### Get past events
GET /api/v1/events/past

Returns all past events.

---

## POST Endpoint

### Create event
POST /api/v1/events

Creates a new event.

Example request body:
```json
{
  "name": "Open Day",
  "organizer": "Student Affairs",
  "description": "Campus open day for new students",
  "url": "https://example.edu/open-day",
  "app_id": "11111111-1111-1111-1111-111111111111",
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

Example request body:
```json
{
  "title": "Updated Event",
  "organizer": "Student Affairs",
  "description": "Updated description",
  "url": "https://example.edu/open-day-updated",
  "app_id": "11111111-1111-1111-1111-111111111111",
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

Marks an event as cancelled.

---

## DELETE Endpoint

### Delete a specific event
DELETE /api/v1/events/{id}

Deletes the event with the given ID.
