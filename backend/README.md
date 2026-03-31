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

## User and Club Linking

Users and clubs are linked automatically.

When you create a user via the client command, the system also creates a club for that user if one does not already exist.

Create or fetch a user and generate a token:

```bash
docker compose exec backend php artisan client:manage person@example.com
```

This will:

- create the user if missing
- generate a Passport token
- auto-create a club owned by that user

Manual fallback: link an existing user to a newly created club:

```bash
docker compose exec backend php artisan club:link-user person@example.com
```

Verify the user has a club:

```bash
docker compose exec backend php artisan tinker --execute="dump(optional(\App\Models\User::where('email', 'person@example.com')->first())->club_id);"
```
