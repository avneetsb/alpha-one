# Technology Stack Specifications

## PHP Standards
- Version: PHP 8.2+.
- Coding: PSR-12; PSR-4 autoload; strict types.

## Approved Components
- Database: `illuminate/database` (Eloquent) with optional `doctrine/dbal` for schema diff.
- Caching: `predis/predis`.
- Logging: `monolog/monolog`.
- Queue: `illuminate/queue` (Redis driver).
- CLI: `symfony/console`.

## Prohibited
- Full-stack frameworks (Laravel/Symfony full apps). Use components only.

