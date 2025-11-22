# API Contracts (Internal Service)

## Standards
- Versioning: `/v1` prefix.
- Auth: API KEY/SECRET HMAC or Bearer; RBAC; rate limit headers; `X-Trace-Id`.
- Envelope: `{ status: 'ok|error', data, error }` with codes.

## Endpoints
- Instruments: `GET /v1/instruments`, `POST /v1/instruments/refresh`.
- Market Data: `POST /v1/ticks/subscribe`, `POST /v1/ticks/unsubscribe`, `GET /v1/ticks/status`.
- Historical: `POST /v1/historical/fetch`, `GET /v1/historical/status/{jobId}`.
- Candles: `POST /v1/candles/aggregate`, `GET /v1/candles/{instrument}/{interval}`.
- Orders: `POST /v1/orders`, `GET /v1/orders/{id}`, `POST /v1/orders/{id}/cancel`, `POST /v1/orders/{id}/modify`.
- Portfolio & Risk: `GET /v1/positions`, `POST /v1/risk/limits`, `GET /v1/risk/status`.
- Strategy: `POST /v1/strategy/start|stop|pause|resume|manual/cancel-order`.
- Optimization: `POST /v1/optimization/start|resume`, `GET /v1/optimization/status/{jobId}`.
- Reconciliation: `POST /v1/reconciliation/run`, `GET /v1/reconciliation/status/{runId}`.
- Reports: `GET /v1/reports/generate`, `GET /v1/dashboard/stream`.
- Lifecycle: `POST /v1/shutdown/graceful`.

## Errors
- Codes: `VALIDATION_ERROR`, `AUTH_REQUIRED`, `FORBIDDEN`, `NOT_FOUND`, `CONFLICT`, `RATE_LIMITED`, `BROKER_ERROR`, `SERVER_ERROR`.

