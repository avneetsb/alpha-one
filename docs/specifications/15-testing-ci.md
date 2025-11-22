# Testing & CI/CD

## Strategy
- Unit: Domain models/services/utilities.
- Integration: Adapters (DB, Cache, Broker API) with sandbox/staging.
- E2E: Critical flows (ticks→candles, order lifecycle, reconciliation).

## Benchmarks
- Tick ingestion p95 ≤ 300ms; candle aggregation p95 ≤ 500ms; order pipeline p95 ≤ 250ms.

## Tools
- `phpunit/phpunit`; mocks with `mockery/mockery`.
- CI: GitHub Actions; coverage thresholds ≥ 80% domain, ≥ 70% adapters.

