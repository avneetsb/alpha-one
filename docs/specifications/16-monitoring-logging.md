# Monitoring & Logging

## Logging
- Monolog async via workers; JSON format; fields: `timestamp, level, component, broker_id, trace_id, message, context`.
- Batch cadence 1s; sampling on debug; DB retention 30 days, archive beyond.

## Metrics
- Prometheus/OpenMetrics export; standardized labels; CLI dashboard refresh 1s.

## Error Handling
- Lossless for `error` level; sanitizer hides secrets; backpressure on DB latency p95 breach.

