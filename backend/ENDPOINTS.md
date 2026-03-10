# Events API Documentation

This document describes the REST API endpoints used to manage events.

Base path:

`/api/events`

---

# Summary

| Method | Endpoint                 | Description                       |
| ------ | ------------------------ |-----------------------------------|
| GET    | /api/events              | Get all events                    |
| GET    | /api/events/{id}         | Get a specific event              |
| GET    | /api/events/upcoming     | Get all upcoming events           |
| GET    | /api/events/past         | Get all past events               |
| POST   | /api/events              | Create a new event                |
| PUT    | /api/events/{id}         | Update a specific event           |
| PATCH  | /api/events/{id}         | Partially update a specific event |
| PATCH  | /api/events/{id}/cancel  | Cancel a specific event           |
| DELETE | /api/events/{id}         | Delete a specific event           |

---

## GET Endpoints

### Get all events
`GET /api/events`

Returns all events.

### Get a specific event
`GET /api/events/{id}`

Returns one specific event by ID.

### Get upcoming events
`GET /api/events/upcoming`

Returns all upcoming events.

### Get past events
`GET /api/events/past`

Returns all past events.

---

## POST Endpoints

### Create event
`POST /api/events`

Creates a new event.

#### Example request body
```json
{
  "title": "Open Day",
  "description": "Campus open day for new students",
  "start_date": "2026-03-20T10:00:00",
  "end_date": "2026-03-20T16:00:00",
  "location": "Main Building"
}
```

---

## PUT Endpoints

### Update a specific event

`PUT /api/events/{id}`

Updates the full event.

### Example

`PUT /api/events/15`

#### Example request body
```json
{
  "title": "Updated Event",
  "description": "Updated description",
  "start_date": "2026-03-20T12:00:00",
  "end_date": "2026-03-20T18:00:00",
  "location": "Conference Hall",
  "status": "scheduled"
}
```

---

## PATCH Endpoints

### Partially update a specific event

`PATCH /api/events/{id}`

Updates only specific fields of an event.

### Example

`PATCH /api/events/15`

#### Example request body

```json
{
  "title": "Updated title"
}
```

### Cancel a specific event

`PATCH /api/events/{id}/cancel`

Marks an event as cancelled.

### Example

`PATCH /api/events/15/cancel`

---

## DELETE Endpoint

### Delete a specific event

`DELETE /api/events/{id}`

Deletes the event with the given ID.

### Example

`DELETE /api/events/15`

---
