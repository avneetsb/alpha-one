# Historical Data

- Daily: `POST /v2/charts/historical`.
- Intraday: `POST /v2/charts/intraday` (`interval` 1/5/15/25/60; ≤90 days per call).
- Dedupe: unique `(instrument_id, ts)`; checksums on OHLCV.
- Streaming fetch; resumable checkpoints; bounded parallelism.

## DB Tables
- `candles_{interval}`: `id PK`, `instrument_id FK`, `ts DATETIME`, `open DECIMAL(10,2)`, `high DECIMAL(10,2)`, `low DECIMAL(10,2)`, `close DECIMAL(10,2)`, `volume INT`, `oi INT NULL`, `checksum VARCHAR(255)`; unique `instrument_id+ts`.

