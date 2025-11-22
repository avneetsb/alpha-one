# Implementation Roadmap

## Order
- Core infra first: logging, config, caching, DB access.
- Then domain models/utilities; then adapters; then features by dependency.

## Milestones
- M1: Bootstrap project, env, logging, Monolog channels; Redis + Predis; MySQL access via Illuminate Database.
- M2: Instrument registry loader from Dhan CSV; cache + DB persistence; CLI commands for refresh/list.
- M3: Market Quote snapshot API adapter; Redis LTP cache; CLI subscribe/status.
- M4: WS ingestion workers (feed + order updates); per-instrument queues; persistence.
- M5: Orders adapter; idempotent placement/modify/cancel; postback handler; CLI order commands.
- M6: Historical fetcher + candle aggregator; DB schema and dedupe.
- M7: Portfolio & funds adapters; risk constraints; reconciliation CLI.
- M8: Testing matrix; CI; dashboards; SLO alerts.

## Atomic Commits
- One feature or schema per commit; adapters behind interfaces; contract tests.

