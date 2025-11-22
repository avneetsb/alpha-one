# Trading Platform CLI Walkthrough

This document outlines the implemented features of the CLI-based Trading Platform and provides instructions on how to run and verify them.

## Overview

The application is built using a Hexagonal Architecture in PHP 8.2+, utilizing `symfony/console` for the CLI, `illuminate/database` for ORM, `monolog` for logging, and `predis` for caching. It supports the Dhan broker via an adapter.

## Prerequisites

- PHP 8.2+
- Composer
- Redis (running on default port 6379)
- SQLite (for local demo) or MySQL

## Setup

1.  **Install Dependencies:**
    ```bash
    composer install
    ```

2.  **Environment Configuration:**
    The `.env` file is configured for local development using SQLite.
    ```bash
    # .env
    DB_CONNECTION=sqlite
    DB_DATABASE=/absolute/path/to/database/database.sqlite
    ```

3.  **Database Migration:**
    Run migrations to create tables (`instruments`, `orders`, `candles_*`, `positions`, `holdings`).
    ```bash
    php bin/console cli:migrate
    ```

## Features & Commands

### 1. Instrument Registry
Manage trading instruments.
- **Refresh Instruments:** Fetches instruments from Dhan (mocked/loader) and saves to DB.
    ```bash
    php bin/console cli:instruments:refresh --broker dhan
    ```
- **List Instruments:** Lists stored instruments.
    ```bash
    php bin/console cli:instruments:list
    ```

### 2. Market Data (Quotes)
Real-time tick data handling.
- **Subscribe:** Subscribe to instruments (updates Redis set).
    ```bash
    php bin/console cli:ticks:subscribe --broker dhan --instrument NIFTY23DECFUT
    ```
- **Status:** Check subscription status.
    ```bash
    php bin/console cli:ticks:status
    ```
- **Ingestion Worker:** Connects to Dhan WebSocket (mocked) and ingests ticks.
    ```bash
    php bin/console cli:workers:tick-ingestion --broker dhan
    ```

### 3. Order Management
Place and manage orders.
- **Place Order:** Creates a local order and sends it to Dhan API (mocked if dummy creds).
    ```bash
    php bin/console cli:order:place --instrument NIFTY23DECFUT --qty 50 --price 22500 --side BUY --type LIMIT --broker dhan
    ```
- **Cancel Order:** Cancels an order.
    ```bash
    php bin/console cli:order:cancel --order-id 1
    ```

### 4. Historical Data
Fetch and manage historical candles.
- **Fetch History:** Fetches candles for a range.
    ```bash
    php bin/console cli:historical:fetch --instrument NIFTY23DECFUT --from 2023-12-01 --to 2023-12-02 --interval 1m
    ```

### 5. Portfolio
View positions and holdings.
- **List Positions:** Lists current open positions.
    ```bash
    php bin/console cli:portfolio:positions
    ```

## Architecture Highlights

- **Domain-Driven:** Core logic in `src/Domain`.
- **Infrastructure:** Adapters for Database, Redis, Logger, and Broker in `src/Infrastructure`.
- **Application:** CLI Commands and Workers in `src/Application`.
- **Config:** Centralized configuration in `config/`.
    - `config/database.php`: Database connection settings.
    - `config/logging.php`: Logging configuration.
    - `config/broker.php`: Broker API URLs and credentials.
    - `config/queue.php`: Queue configuration.

### 6. Strategy Engine
- **Test Strategy:** Run a test strategy to verify the engine.
    ```bash
    php bin/console cli:strategy:test
    ```

### 7. Queue & Workers
- **Test Queue:** Dispatch a test job.
    ```bash
    php bin/console cli:queue:test
    ```
- **Run Worker:** Start the queue worker.
    ```bash
    php bin/console cli:queue:work --connection redis --queue default
    ```

### 8. Reporting
- **Generate Report:**
    ```bash
    php bin/console cli:report:generate
    ```

### 9. Reconciliation
- **Run Reconciliation:**
    ```bash
    php bin/console cli:recon:run
    ```

### 10. System Health
- **Run Health Check:**
    ```bash
    php bin/console cli:system:health
    ```

### 11. Advanced Queue
- **Run Worker with Priority:**
    ```bash
    php bin/console cli:queue:work --connection redis --queue high,default,low
    ```

### 12. High Availability
- **Leader Election:** `LeaderElection` service is available for coordination.
- **Cache Locking:** `RedisAdapter::acquireLock` prevents stampedes.
- **Poison Messages:** Failed jobs are moved to DLQ after retries.

### 13. Advanced Analytics
- **Gap Filling:** `GapFiller` service handles missing candle data.
- **Corporate Actions:** `CorporateActionService` processes dividends, splits, mergers.
- **Stress Testing:** `StressTestService` simulates market scenarios and Monte Carlo.

### 14. Production Readiness Features

**P1: Instrument Management** ✅
- Real CSV parsing with `league/csv`
- Downloads from `https://images.dhan.co/api-data/api-scrip-master.csv`
- Validates ~20+ columns with proper data types
- Error handling for malformed rows

**P2: Fee Calculator** ✅  
- NSE: EQUITY, F&O rates (STT 0.1%/0.025%, Txn 0.00345%/0.0019%)
- BSE: EQUITY, F&O rates (STT 0.1%/0.025%, Txn 0.00375%/0.0025%)
- MCX: Commodity rates (CTT 0.01%, Txn 0.0019%)
- GST (18%), Stamp Duty (0.003%-0.015%), SEBI charges (₹10/crore)

**P3: Margin Calculator** ✅
- SPAN margin (10-12% of contract value)
- Exposure margin (5-20% based on product type)
- Separate calculations for NRML, MIS, CNC
- Option margin: Max(10% spot, premium)

**P4: Risk Management** ✅
- Parametric VaR at 95% confidence
- Position limits: ₹10L per position, 5 positions/instrument
- Daily loss limit: ₹1L
- Portfolio VaR limit: 10% of portfolio value

### 15. Broker Integration (Dhan)

**Authentication**:
- JWT Access Token (24hr validity)
- Renewable via `/v2/RenewToken`
- Static IP required for order APIs

**API Endpoints**:
```
POST   /v2/orders           - Place order
PUT    /v2/orders/{id}      - Modify order
DELETE /v2/orders/{id}      - Cancel order
GET    /v2/orders           - Orderbook
GET    /v2/trades           - Tradebook
GET    /v2/portfolio        - Positions
```

**WebSocket Feed**:
- URL: `wss://api-feed.dhan.co`
- Binary protocol (little-endian)
- 3 modes: Ticker (17B), Quote (51B), Full (163B)
- Max 5 connections, 5000 instruments each

**Testing Commands**:
```bash
# Load instruments
php bin/console cli:instruments:refresh --broker dhan

# Test profile
php bin/console cli:profile:check

# Place order (requires static IP)
php bin/console cli:order:place --symbol RELIANCE --side BUY --qty 1
```

## Next Steps

- **Live Credentials**: Replace dummy credentials in `.env` with real tokens
- **Static IP Setup**: Configure static IP from Dhan Web for order placement
- **Strategy Deployment**: Deploy automated strategies with risk checks
- **Monitoring**: Set up logging, alerting, and performance tracking
- **Testing**: Comprehensive testing with sandbox/paper trading
- **Production Deployment**: Deploy to production with proper infrastructure
