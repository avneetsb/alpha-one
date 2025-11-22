# Migration System (Custom)

## Design
- Versioned SQL files under `supabase/migrations` or local `database/migrations` with semantic names.
- `migrations` table tracks `version`, `applied_at`, `checksum`.
- CLI: `db:migrate`, `db:rollback --to=<version>`; environment-aware via `.env`.
- Rollbacks: paired down-migrations; guard additive-only policy.

## Conventions
- Naming: `YYYYMMDDHHMMSS__description.sql`.
- Schema normalization: 3NF/BCNF; composite indexes; FKs with `CASCADE`/`SET NULL`.

