# Portfolio & Funds

- Holdings: `GET /v2/holdings` → positions in demat.
- Positions: `GET /v2/positions`; convert: `POST /v2/positions/convert`.
- Funds: `POST /v2/margincalculator`; `GET /v2/fundlimit`.

## Internal Models
- Position, Holding, FundLimit, MarginQuote.

## DB Tables
- `positions`, `holdings`, `fund_limits`, `margin_quotes` with foreign keys to users/brokers.

