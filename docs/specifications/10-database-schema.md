# Database Schema (MySQL)

## Conventions
- Tables/columns: `snake_case`.
- Types: `INT`, `VARCHAR(255)`, `TEXT`, `DATETIME`, `DECIMAL(10,2)`, `TINYINT(1)`, `JSON`, `ENUM`.
- Relationships: FK with `ON DELETE/UPDATE CASCADE` or `SET NULL`; soft deletes via `deleted_at`.

## Core Tables
- `users`: `id PK`, `uuid`, `name`, `email`, `created_at`, `updated_at`.
- `brokers`: `id PK`, `name`, `code`, `created_at`.
- `broker_credentials`: `id PK`, `user_id FK`, `broker_id FK`, `access_token`, `expires_at`, `static_ip_primary`, `static_ip_secondary`, `created_at`, `updated_at`.
- `instruments` (see 02-instrument-management).
- `ticks`: `id PK`, `instrument_id FK`, `ts DATETIME`, `ltp DECIMAL(10,2)`, `ltt DATETIME`, `buy_qty INT`, `sell_qty INT`, `volume INT`; index `(instrument_id, ts)` unique.
- `candles_1m`, `candles_5m`, `candles_15m`, `candles_25m`, `candles_60m`, `candles_1d` (see 04-historical-data).
- `orders`: `id PK`, `user_id FK`, `broker_id FK`, `instrument_id FK`, `client_order_id`, `idempotency_key`, `side ENUM('BUY','SELL')`, `type ENUM('LIMIT','MARKET','STOP_LOSS','STOP_LOSS_MARKET')`, `validity ENUM('DAY','IOC')`, `qty INT`, `price DECIMAL(10,2)`, `status ENUM('QUEUED','PENDING','REJECTED','CANCELLED','TRADED','EXPIRED')`, `broker_order_id`, `created_at`, `updated_at`.
- `order_events` (see 07-postbacks-live-updates).
- `trades`: `id PK`, `order_id FK`, `exchange_order_id`, `exchange_trade_id`, `qty INT`, `price DECIMAL(10,2)`, `created_at`.
- `positions`, `holdings` (see 06-portfolio-funds).
- `fund_limits`, `margin_quotes` (see 06-portfolio-funds).
- `logs`: structured JSON logs; partition by date; `level`, `trace_id`, `component`, `payload JSON`, `created_at`.
- `migrations`: `id PK`, `version`, `applied_at`, `checksum`.
- `rate_limits`: `id PK`, `scope`, `limit INT`, `window_seconds INT`, `created_at`.

## Indexing Strategy
- Primary keys on `id`.
- Unique constraints: `orders.idempotency_key`, `instruments.exchange+symbol`.
- FKs with indexes: `*_id` columns.
- Composite indexes: `ticks(instrument_id, ts)`, `candles_*(instrument_id, ts)`.
- Frequent WHERE columns: `orders.status`, `order_events.order_id`, `positions.instrument_id`.

## ER Diagram
```mermaid
erDiagram
  users ||--o{ broker_credentials : has
  brokers ||--o{ broker_credentials : has
  instruments ||--o{ ticks : has
  instruments ||--o{ candles_1m : has
  instruments ||--o{ candles_5m : has
  instruments ||--o{ candles_15m : has
  instruments ||--o{ candles_25m : has
  instruments ||--o{ candles_60m : has
  instruments ||--o{ candles_1d : has
  users ||--o{ orders : places
  brokers ||--o{ orders : via
  instruments ||--o{ orders : for
  orders ||--o{ trades : generates
  orders ||--o{ order_events : emits
  users ||--o{ positions : holds
  instruments ||--o{ positions : on
  users ||--o{ holdings : holds
  instruments ||--o{ holdings : of

  users {
    int id PK
    varchar uuid
    varchar email
  }
  brokers {
    int id PK
    varchar code
  }
  instruments {
    int id PK
    varchar exchange
    varchar symbol
    enum instrument_type
  }
  orders {
    int id PK
    int user_id FK
    int broker_id FK
    int instrument_id FK
    varchar idempotency_key UNIQUE
    enum status
  }
  trades {
    int id PK
    int order_id FK
  }
  order_events {
    int id PK
    int order_id FK
    json payload
  }
```
