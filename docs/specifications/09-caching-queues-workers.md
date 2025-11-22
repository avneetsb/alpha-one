# Caching, Queues, Workers

- Cache: Redis read-through, write-behind for aggregates. Keys: `app:domain:entity:id:v{N}`.
- Queues: Redis-backed multi-priority lanes; DLQ; backoff with jitter; per-broker rate limiting.
- Workers: heartbeat, leader election, active-active WS ingestors.

## PHP Libraries
- Cache: `predis/predis`.
- Queue: `illuminate/queue`.
- Scheduling: `symfony/process` or cron; CLI via `symfony/console`.

## Metrics
- `cache.hit_ratio`, `queue_depth`, `worker_uptime`.

