# Overview

- Purpose: CLI and API-based trading platform for Indian exchanges with hexagonal architecture, multi-broker support (initial: DhanHQ), rigorous observability, migrations, and a full testing matrix.
- Sources: trading-platform-requirements.md; DhanHQ v2 API docs in `dhan-api-documentation/`.
- Categories: Authentication, Instruments, Market Data, Historical Data, Orders, Portfolio & Funds, Postbacks & Live Updates, Risk & Controls, Caching & Queues & Workers, Database, Architecture, API Contracts, Migration System, Security & Validation, Testing & CI, Monitoring & Logging, Implementation Roadmap.

## Cross-Reference Summary (DhanHQ v2)
- Authentication: access tokens, OAuth consent flows; static IP management; TOTP; profile (`#2 Authentication`).
- Instruments: CSV masters, segment list endpoint (`#4 Instrument List`).
- Market Data: WebSocket feed, binary payloads; Market Quote snapshot APIs (`#15 Live Market Feed`, `#16 Market Quote`).
- Full Depth: 20/200 levels via WebSocket (`#13 Full Market Depth`).
- Historical: OHLC daily/intraday POST endpoints (`#22 Historical Data`).
- Orders: REST order lifecycle (place/modify/cancel, book/trade book) (`#7 Orders`).
- Live Order Update: WebSocket JSON (`#5 Live Order Update`).
- Postback: HTTP webhook (`#14 Postback`).
- Portfolio & Funds: holdings/positions; margin calculator & fund limits (`#18 Portfolio`, `#21 Funds`).
- Trader's Control: kill switch (`#9 Trader's Control`).

