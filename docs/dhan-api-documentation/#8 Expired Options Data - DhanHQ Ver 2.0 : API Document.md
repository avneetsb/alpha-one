[Skip to content](https://dhanhq.co/docs/v2/expired-options-data/#expired-options-data)



You are on the latest version of DhanHQ API.



[![logo](https://dhanhq.co/docs/v2/img/DhanHQ_logo.svg)](https://dhanhq.co/ "Ver 2.0 / API Documentation")

Ver 2.0 / API Documentation



Expired Options Data



Type to start searching

[![logo](https://dhanhq.co/docs/v2/img/DhanHQ_logo.svg)](https://dhanhq.co/ "Ver 2.0 / API Documentation")
Ver 2.0 / API Documentation


- [Introduction](https://dhanhq.co/docs/v2/)
- [Authentication](https://dhanhq.co/docs/v2/authentication/)
- [ ]
Trading APIs

Trading APIs


- [Orders](https://dhanhq.co/docs/v2/orders/)
- [Super Order](https://dhanhq.co/docs/v2/super-order/)
- [Forever Order](https://dhanhq.co/docs/v2/forever/)
- [Portfolio](https://dhanhq.co/docs/v2/portfolio/)
- [EDIS](https://dhanhq.co/docs/v2/edis/)
- [Trader's Control](https://dhanhq.co/docs/v2/traders-control/)
- [Funds](https://dhanhq.co/docs/v2/funds/)
- [Statement](https://dhanhq.co/docs/v2/statements/)
- [Postback](https://dhanhq.co/docs/v2/postback/)
- [Live Order Update](https://dhanhq.co/docs/v2/order-update/)

- [x]
Data APIs

Data APIs


- [Market Quote](https://dhanhq.co/docs/v2/market-quote/)
- [Live Market Feed](https://dhanhq.co/docs/v2/live-market-feed/)
- [Full Market Depth](https://dhanhq.co/docs/v2/full-market-depth/)
- [Historical Data](https://dhanhq.co/docs/v2/historical-data/)
- [ ]
Expired Options Data
[Expired Options Data](https://dhanhq.co/docs/v2/expired-options-data/)
Table of contents


- [Historical Rolling Data](https://dhanhq.co/docs/v2/expired-options-data/#historical-rolling-data)

- [Option Chain](https://dhanhq.co/docs/v2/option-chain/)

- [Annexure](https://dhanhq.co/docs/v2/annexure/)
- [Instrument List](https://dhanhq.co/docs/v2/instruments/)
- [Releases](https://dhanhq.co/docs/v2/releases/)

Table of contents


- [Historical Rolling Data](https://dhanhq.co/docs/v2/expired-options-data/#historical-rolling-data)

# Expired Options Data

This API gives you expired options contract data. We have pre processed data for you to get it on rolling basis i.e. you can fetch last 5 years of strike wise data based on ATM and upto 10 strikes above and below. In addition to that, the data values are open, high, low, close, implied volatility, volume, open interest and spot information as well.

|     |     |     |
| --- | --- | --- |
| POST | /charts/rollingoption | Get Continuous Expired Options Contract data |

## Historical Rolling Data

Fetch expired options data on a rolling basis, along with the Open Interest, Implied Volatility, OHLC, Volume as well as information about the spot. You can fetch for upto 30 days of data in a single API call. Expired options data is stored on a minute level, based on strike price relative to spot (example ATM, ATM+1, ATM-1, etc.).

You can fetch data upto last 5 years. We have added both Index Options and Stock Options data on this.

```
curl --request POST \
--url https://api.dhan.co/v2/charts/rollingoption \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--header 'access-token: ' \
--data '{}'
```

**Request Structure**

```
    {
    "exchangeSegment": "NSE_FNO",
    "interval": "1",
    "securityId": 13,
    "instrument": "OPTIDX",
    "expiryFlag": "MONTH",
    "expiryCode": 1,
    "strike": "ATM",
    "drvOptionType": "CALL",
    "requiredData": [\
        "open",\
        "high",\
        "low",\
        "close",\
        "volume"\
    ],
    "fromDate": "2021-08-01",
    "toDate": "2021-09-01"
    }
```

**Parameters**

| Field | Field Type | Description |
| --- | --- | --- |
| exchangeSegment<br>_required_ | enum string | Exchange & segment for which data is to be fetched - [here](https://dhanhq.co/docs/v2/annexure/#exchange-segment) |
| interval<br> <br>_required_ | enum integer | Minute intervals in timeframe <br>`1`, `5`, `15`, `25`, `60` |
| securityId<br>_required_ | string | Underlying exchange standard ID for each scrip. Refer [here](https://dhanhq.co/docs/v2/instruments/) |
| instrument <br> <br>_required_ | enum string | Instrument type of the scrip. Refer [here](https://dhanhq.co/docs/v2/annexure/#instrument) |
| expiryCode<br>_required_ | enum integer | Expiry of the instruments. Refer [here](https://dhanhq.co/docs/v2/instruments/) |
| expiryFlag<br>_required_ | enum string | Expiry intervale of the instrument<br>`WEEK` or `MONTH` |
| strike<br>_required_ | enum string | `ATM` for At the Money<br> Upto `ATM+10 / ATM-10` for Index Options near expiry<br> Upto `ATM+3 / ATM-3` for all other contracts |
| drvOptionType<br>_required_ | enum string | `CALL` or `PUT` |
| requiredData<br>_required_ | array \[\] | Array of all required parameters<br>`open``high``low``close``iv``volume``strike``oi``spot` |
| fromDate<br>_required_ | string | Start date of the desired range |
| toDate<br>_required_ | string | End date of the desired range (non-inclusive) |

**Response Structure**

```
{
    "data": {
        "ce": {
        "iv": [],
        "oi": [],
        "strike": [],
        "spot": [],
        "open": [\
            354,\
            360.3\
        ],
        "high": [],
        "low": [],
        "close": [],
        "volume": [],
        "timestamp": [\
            1756698300,\
            1756699200\
        ]
        },
        "pe": null
    }
}
```

**Parameters**

| Field | Field Type | Description |
| --- | --- | --- |
| open | float | Open price of the timeframe |
| high | float | High price in the timeframe |
| low | float | Low price in the timeframe |
| close | float | Close price of the timeframe |
| volume | int | Volume traded in the timeframe |
| timestamp | int | Epoch timestamp |

Note: For description of enum values, refer [Annexure](https://dhanhq.co/docs/v2/annexure)

Copyright © 2024 Moneylicious Securities Private Limited


![HQ Bot](https://dhanhq.co/assets/svg/hq.svg)

### DhanHQ Chatbot

###### Power by

Show me how to place a buy order

How to get market data using Python?

How do i handle errors in Dhan APIs?

What are the authentication requirements?

Today

![HQ Bot](https://dhanhq.co/assets/svg/hq.svg)

Hi! I'm your assistant. How can I help you today?

09:39 AM

Need Help?