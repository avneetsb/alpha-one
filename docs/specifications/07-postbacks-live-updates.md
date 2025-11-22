# Postbacks & Live Order Update

- Postback: HTTP POST to configured URL with order payload.
- Live Order Update: WS `wss://api-order-update.dhan.co` with JSON messages.

## Contracts
- Validate signatures if provided; enforce rate-limited processing; persist updates to `order_events` table.

## DB Tables
- `order_events`: `id PK`, `order_id`, `broker_order_id`, `event_type`, `payload JSON`, `created_at`.

