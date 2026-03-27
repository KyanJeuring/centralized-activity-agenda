# Database Backup Policy & Procedures

## 1. Overview
The database for the Centralized Activity Agenda is automatically backed up using a detached `postgres-backup-local` Docker container. This sidecar container runs alongside the main PostgreSQL database and handles scheduling, dumping, compression, and rotation all without requiring host-level cron jobs.

## 2. Backup Schedule
Backups are triggered **daily at midnight (00:00 server time)**.
This is controlled by the `SCHEDULE: '@daily'` environment variable in `compose.yaml`.

## 3. Retention Policy
The system uses a "Grandfather-Father-Son" retention strategy to save disk space while maximizing the ability to roll back in time. Old backups are automatically pruned.

- **Daily Backups:** We keep 1 backup for each of the last **7 days**.
- **Weekly Backups:** We keep 1 backup for each of the last **4 weeks**.
- **Monthly Backups:** We keep 1 backup for each of the last **2 months**.

At any given time, there will be a maximum of 13 compressed backup files on the host machine.

## 4. Storage Location
Backups are exported to the host machine in the following directory:
`./database/backups/`

Inside this directory, there are automatically generated subfolders:
- `/daily` 
- `/weekly`
- `/monthly`
- `/last` (Always contains the absolute most recent backup, plus a `-latest.sql.gz` symlink tool)

*Note: The `/database/backups` directory is included in `.gitignore` to prevent sensitive database dumps from being committed to source control.*

## 5. Manual Operations

### Triggering a manual backup
If you need to take a backup immediately (e.g., before running a risky migration), run:
```bash
docker container exec postgres_backup /backup.sh
```

### Restoring a backup
If the database needs to be restored from a `.sql.gz` dump, use the command below. 
*(Replace `*latest.sql.gz` with a specific filename if you want to restore a historical backup instead of the most recent one).*

```bash
docker container exec postgres_backup sh -c 'export PGPASSWORD=$POSTGRES_PASSWORD; zcat /backups/last/*latest.sql.gz | psql -h database -U $POSTGRES_USER -d $POSTGRES_DB -q'
```

**Warning:** This command will attempt to recreate tables and insert data. If you need a completely clean restore, it is recommended to wipe the database first using Laravel:
```bash
docker compose exec backend php artisan db:wipe --drop-views --force
```