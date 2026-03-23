## API Documentation (Swagger)

Swagger UI is available at:

- `/api/documentation`

Included endpoints now cover the Events API surface under `/api/v1/events` (list, detail, upcoming, past, create, replace, patch, cancel, delete).

Generated OpenAPI JSON is written to:

- `storage/api-docs/api-docs.json`

To regenerate docs manually inside the backend container:

```bash
docker compose exec backend php artisan l5-swagger:generate
```
