# Broker Integration Guide - Dhan

This guide provides step-by-step instructions for integrating the trading platform with Dhan's broker APIs based on their official V2 API documentation.

## Prerequisites

1. **Dhan Trading Account**: Sign up at [dhan.co](https://dhan.co/)
2. **Access Token or API Key/Secret**: Generate from [web.dhan.co](https://web.dhan.co/) -> My Profile -> Access DhanHQ APIs
3. **Static IP (for order placement)**: Required as per SEBI guidelines

## Authentication Methods

### Method 1: Access Token (Recommended for Individual Traders)

**24-hour validity token** - Generate directly from Dhan Web:

1. Login to [web.dhan.co](https://web.dhan.co/)
2. Navigate to: My Profile -> Access DhanHQ APIs
3. Generate "Access Token" (valid for 24 hours)
4. Update .env:
   ```
   DHAN_ACCESS_TOKEN=eyJh...your.token.here
   DHAN_CLIENT_ID=your_client_id
   ```

**Token Refresh**: Use the following endpoint (within 24hrs):
```bash
curl --location 'https://api.dhan.co/v2/RenewToken' \
--header 'access-token: YOUR_TOKEN' \
--header 'dhanClientId: YOUR_CLIENT_ID'
```

### Method 2: API Key/Secret (for automated systems)

**12-month validity** - Better for automated trading:

1. Login to [web.dhan.co](https://web.dhan.co/)
2. Navigate to: My Profile -> Access DhanHQ APIs
3. Toggle to "API key" and create application
4. Save API Key and Secret
5. Update .env:
   ```
   DHAN_API_KEY=your_api_key
   DHAN_API_SECRET=your_api_secret
   ```

Then follow the 3-step OAuth flow (see Authentication docs #2).

## API Endpoints

### Base URLs

- **Production API**: `https://api.dhan.co`
- **WebSocket Feed**: `wss://api-feed.dhan.co`
- **Authentication**: `https://auth.dhan.co`

### Key Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/v2/orders` | Place order |
| PUT | `/v2/orders/{order-id}` | Modify order |
| DELETE | `/v2/orders/{order-id}` | Cancel order |
| GET | `/v2/orders` | Get orderbook |
| GET | `/v2/trades` | Get tradebook |
| GET | `/v2/portfolio` | Get positions |
| GET | `/v2/profile` | Verify token/account |

## Configuration Steps

### Step 1: Update `.env` File

Copy the template `.env` file and fill in your credentials:

```bash
# Authentication
DHAN_ACCESS_TOKEN=your_token_here
DHAN_CLIENT_ID=your_client_id_here

# Optional: API Key Auth
DHAN_API_KEY=your_api_key
DHAN_API_SECRET=your_api_secret
```

### Step 2: Set Up Static IP (Required for Order Placement)

**IMPORTANT**: Static IP is mandatory for order placement APIs as per SEBI/Exchange guidelines.

**Option A**: Set via Dhan Web
1. Login to [web.dhan.co](https://web.dhan.co/)
2. Navigate to DhanHQ APIs section
3. Set Primary and Secondary static IPs

**Option B**: Set via API
```bash
curl --request POST \
--url https://api.dhan.co/v2/ip/setIP \
--header 'access-token: YOUR_TOKEN' \
--header 'Content-Type: application/json' \
--data '{
  "dhanClientId": "YOUR_CLIENT_ID",
  "ip": "YOUR_STATIC_IP",
  "ipFlag": "PRIMARY"
}'
```

### Step 3: Verify Authentication

Test your setup:

```bash
php bin/console test:profile
```

This calls the `/v2/profile` endpoint to verify your token and account status.

## Testing Integration

### 1. Load Instruments

```bash
php bin/console cli:instruments:refresh --broker dhan
```

This will:
- Download CSV from `https://images.dhan.co/api-data/api-scrip-master.csv`
- Parse using `league/csv`
- Save ~20,000+ instruments to database

### 2. List Instruments

```bash
php bin/console cli:instruments:list --limit 10
```

### 3. Place Test Order (Requires Static IP)

```bash
php bin/console cli:order:place \
  --symbol RELIANCE \
  --side BUY \
  --quantity 1 \
  --price 2500 \
  --product INTRADAY \
  --type LIMIT
```

### 4. WebSocket Market Data

```bash
php bin/console cli:market:subscribe RELIANCE TCS
php bin/console cli:workers:tick-ingestion
```

## API Response Formats

### Order Placement Response

```json
{
  "orderId": "112111182198",
  "orderStatus": "PENDING"
}
```

### Order Status

Possible values:
- `TRANSIT` - Order sent to exchange
- `PENDING` - Order pending at exchange
- `REJECTED` - Order rejected
- `CANCELLED` - Order cancelled
- `PART_TRADED` - Partially filled
- `TRADED` - Fully executed ExpiredERROR` - Order expired

### Error Handling

The API returns error codes in `omsErrorCode` and `omsErrorDescription` fields:

```json
{
  "omsErrorCode": "400",
  "omsErrorDescription": "Invalid security ID"
}
```

## WebSocket Feed

### Connection

```
wss://api-feed.dhan.co?version=2&token=YOUR_TOKEN&clientId=YOUR_CLIENT_ID&authType=2
```

### Binary Protocol

All WebSocket responses are **binary, little-endian** format (not JSON).

**Packet Structure**:
- Header: 8 bytes (feed code, length, segment, security ID)
- Payload: Varies by subscription mode

**Feed Modes**:
1. **Ticker** (RequestCode: 15) - LTP + Time (17 bytes total)
2. **Quote** (RequestCode: 16) - Full trade data (51 bytes total)
3. **Full** (RequestCode: 17) - Trade + Market Depth (163 bytes total)

**Example Subscription**:
```json
{
  "RequestCode": 15,
  "InstrumentCount": 2,
  "InstrumentList": [
    {
      "ExchangeSegment": "NSE_EQ",
      "SecurityId": "11536"
    },
    {
      "ExchangeSegment": "BSE_EQ",
      "SecurityId": "532540"
    }
  ]
}
```

## Production Checklist

Before going live:

- [ ] Valid access token or API key/secret configured
- [ ] Static IP whitelisted on Dhan
- [ ] Instruments loaded successfully
- [ ] Test order placement in sandbox (if available)
- [ ] WebSocket connection established
- [ ] Fee calculations validated against actual broker statements
- [ ] Risk management limits configured
- [ ] Logging and monitoring tools setup
- [ ] Error handling tested for common scenarios
- [ ] Redis and Database properly configured

## Troubleshooting

**Token Expired**:
- Tokens expire after 24 hours
- Use RenewToken endpoint or generate new token

**Invalid IP**:
- Ensure static IP is whitelisted
- Order placement APIs require static IP
- Data APIs do not require static IP

**WebSocket Disconnects**:
- Implement ping/pong (automated in most WS libraries)
- Server sends ping every 10s, expects pong within 40s
- Maximum 5 concurrent WebSocket connections per user

**Rate Limits**:
- Use the built-in `RateLimiter` service
- Implement exponential backoff for retries

## Support Resources

- **Official Docs**: [dhanhq.co/docs/v2](https://dhanhq.co/docs/v2/)
- **Dhan Support**: support@dhan.co
- **Trading APIs Hub**: [dhanhq.co/trading-apis](https://dhanhq.co/trading-apis)

## Next Steps

Once broker integration is complete:
1. Implement strategy execution with real market data
2. Set up portfolio reconciliation
3. Configure automated P&L tracking
4. Deploy to production environment
