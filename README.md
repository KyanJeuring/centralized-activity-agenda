# CAA - Centralized Activity

## Project Overview

CAA is a comprehensive events management platform that allows users to view all upcoming events from the hubs and clubs in the Northern of the Netherlands.

> [!IMPORTANT]
> This project is designed to run as a Docker Compose deployment — the frontend, backend, and database run as containers.
> It is not intended to be deployed as a traditional single-server Laravel application (shared PHP hosting).
> To run in production the target server must have `Docker` and `Docker Compose` installed; see the **Production** section for details.

## Technology Stack

- **Backend**: Laravel 11 (PHP) with Passport OAuth for API authentication
- **Frontend**: Vue 3 with Vite and Bootstrap Icons for the user interface
- **Database**: PostgreSQL 17 for persistent application data
- **Orchestration**: Docker Compose for local service management
- **Admin Tool**: pgAdmin 4 for database administration
- **API Documentation**: Swagger/OpenAPI for interactive endpoint docs

## Prerequisites

- **Docker** and **Docker Compose** installed
- **Git** for version control
- A text editor or IDE

## Project Structure

```
caa/
├── backend/                      # Laravel API application
│   ├── app/                      # Core application logic
│   ├── config/                   # Application configuration files
│   ├── database/                 # Migrations, factories, and seeders
│   ├── routes/                   # API route definitions
│   ├── tests/                    # Automated test suites
│   ├── storage/                  # Runtime storage for logs and generated docs
│   ├── .env.example              # Backend environment template
│   ├── Dockerfile                # Backend container image definition
│   └── Dockerfile.production     # Production backend image definition
├── frontend/                     # Vue 3 application
│   ├── src/                      # Vue components, views, and pages
│   ├── public/                   # Static public assets
│   ├── package.json              # Frontend package manifest
│   ├── Dockerfile                # Frontend container image definition
│   └── Dockerfile.production     # Production frontend image definition
├── database/                     # Database-related configuration and assets
│   ├── sql/                      # SQL scripts and stored procedures
│   ├── pgadmin/                  # pgAdmin configuration files
│   └── backups/                  # Database backup files
├── compose.yaml                  # Development service orchestration
├── compose.prod.yaml             # Production service orchestration
└── README.md                     # Project documentation
```

## Quick Start

### 1. Clone and Setup Environment

```bash
# Clone the repository (if not already done)
git clone <repository-url> caa
cd caa

# Copy environment files
# See Configuration chapter

# Create Docker volumes and network
docker compose up -d
```

### 2. Initialize the Backend

```bash
# Install PHP dependencies
docker compose exec backend composer install

# Generate application key
docker compose exec backend php artisan key:generate

# Run database migrations
docker compose exec backend php artisan migrate

# Generate API documentation
docker compose exec backend php artisan l5-swagger:generate

# Create a user and token for testing
docker compose exec backend php artisan client:manage person@example.com
```

### 3. Access the Application

- **Frontend**: http://localhost:4200
- **Backend API**: http://localhost:8000
- **API Documentation**: http://localhost:8000/api/documentation
- **pgAdmin**: http://localhost:5050

#### Default pgAdmin Credentials

Access `pgAdmin` to manage the database:
- Email: `admin@example.com` (configure in `.env`)
- Password: `adminpassword` (configure in `.env`)

## Configuration

### Environment Files

Two `.env` files are used for configuration. Copy each from their respective `.example` files and customize as needed.

#### 1. Root `.env` File (`.env`)

Located in the project root for Docker Compose services configuration.

**Copy the template:**

- **Linux/macOS**: `cp .env.example .env`
- **Windows (PowerShell)**: `Copy-Item .env.example .env`
- **Windows (CMD)**: `copy .env.example .env`

#### 2. Backend `.env` File (`backend/.env`)

Located in the backend directory for Laravel application configuration.

**Copy the template:**

- **Linux/macOS**: `cp backend/.env.example backend/.env`
- **Windows (PowerShell)**: `Copy-Item backend\.env.example backend\.env`
- **Windows (CMD)**: `copy backend\.env.example backend\.env`

See `backend/.env.example` for all available Laravel configuration options.

### pgAdmin Configuration

pgAdmin server connections are pre-configured in `database/pgadmin/servers.json`.

**Copy the template:**

- **Linux/macOS**: `cp database/pgadmin/servers.example.json database/pgadmin/servers.json`
- **Windows (PowerShell)**: `Copy-Item database\pgadmin\servers.example.json database\pgadmin\servers.json`
- **Windows (CMD)**: `copy database\pgadmin\servers.example.json database\pgadmin\servers.json`

This file pre-configures the PostgreSQL connection for pgAdmin, allowing immediate access to the database management interface upon startup.

> [!IMPORTANT]
> The username and password in `servers.json` must match the `POSTGRES_USER` and `POSTGRES_PASSWORD` values from your root `.env` file.

## Running the Application

### Start Services

```bash
docker compose up -d
```

### View Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f database
```

### Stop Services

```bash
docker compose down
```

### Stop and Remove Volumes (Reset Database)

```bash
docker compose down -v
```

## Key Features

### API Endpoints

All API endpoints are prefixed with `/api/v1/`:

- **Events**: `GET|POST /events`, `GET|PATCH|DELETE /events/{id}`, `POST /events/{id}/cancel`
- **Clubs**: Manage club information
- **Users**: User management and authentication
- **OAuth**: Token generation via Passport

Full API documentation is available at `/api/documentation` when the backend is running.

### User and Club Management

Users and clubs are automatically linked:

```bash
# Create/fetch user and generate token
docker compose exec backend php artisan client:manage person@example.com

# Link user to club
docker compose exec backend php artisan club:link-user person@example.com

# Verify user has club
docker compose exec backend php artisan tinker --execute="dump(optional(\App\Models\User::where('email', 'person@example.com')->first())->club_id);"
```

### Generated API Documentation

The OpenAPI/Swagger specification is generated at:
- `storage/api-docs/api-docs.json`

Regenerate manually:

```bash
docker compose exec backend php artisan l5-swagger:generate
```

## Database

### Backups

Automated daily backups are created in `./database/backups/`:
- **Daily**: `./database/backups/daily/`
- **Weekly**: `./database/backups/weekly/`
- **Monthly**: `./database/backups/monthly/`

### Database Utilities

Access the database directly:

```bash
# PostgreSQL command line
docker compose exec database psql -U caa_user -d caa

# Run SQL files
docker compose exec database psql -U caa_user -d caa -f /var/www/database_sql/tables.sql
```

## Development

### Backend Development

```bash
# Run tests
docker compose exec backend php artisan test

# Generate database migrations
docker compose exec backend php artisan make:migration create_table_name

# Access Laravel Tinker (interactive shell)
docker compose exec backend php artisan tinker
```

### Frontend Development

The frontend is configured with hot module replacement (HMR) for live updates during development.

Changes to files in `frontend/src/` are automatically reflected in the browser.

## Production

The production stack runs the frontend, backend, and database together in Docker Compose.

The frontend is built as a static Vue app and served by Nginx.
The backend runs in production mode with `APP_ENV=production` and `APP_DEBUG=false`.

Use the production compose file to build and run the full stack:
```bash
docker compose -f compose.prod.yaml up --build -d
```

The frontend bundle is baked with `http://localhost:${BACKEND_PORT}/api/v1` so it can talk to the backend without additional runtime configuration.

Frontend: `http://localhost:${FRONTEND_PORT}`

Backend: `http://localhost:${BACKEND_PORT}`

## Troubleshooting

### Backend Won't Start

Check logs for issues:
```bash
docker compose logs backend
```

Common issues:
- Missing `backend/.env` file
- PHP dependencies not installed: `docker compose exec backend composer install`
- Database connection failing: ensure database service is healthy

### Frontend Connection Issues

If frontend can't connect to backend:
- Verify backend is running: `docker compose ps`
- Check API URL configuration in frontend environment
- Ensure CORS is properly configured in `backend/config/cors.php`

### Database Issues

Reset the database:
```bash
docker compose down -v
docker compose up -d
docker compose exec backend php artisan migrate
```

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vue 3 Documentation](https://vuejs.org)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Docker Documentation](https://docs.docker.com)

## Support

For issues or questions, refer to the individual README files:
- [Backend README](backend/README.md)
- [API Endpoints](backend/ENDPOINTS.md)
- [Backup Policy](BACKUP_POLICY.md)
