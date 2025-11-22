[Skip to content](https://dhanhq.co/docs/v2/portfolio/#portfolio)



You are on the latest version of DhanHQ API.



[![logo](https://dhanhq.co/docs/v2/img/DhanHQ_logo.svg)](https://dhanhq.co/ "Ver 2.0 / API Documentation")

Ver 2.0 / API Documentation



Portfolio



Type to start searching

[![logo](https://dhanhq.co/docs/v2/img/DhanHQ_logo.svg)](https://dhanhq.co/ "Ver 2.0 / API Documentation")
Ver 2.0 / API Documentation


- [Introduction](https://dhanhq.co/docs/v2/)
- [Authentication](https://dhanhq.co/docs/v2/authentication/)
- [x]
Trading APIs

Trading APIs


- [Orders](https://dhanhq.co/docs/v2/orders/)
- [Super Order](https://dhanhq.co/docs/v2/super-order/)
- [Forever Order](https://dhanhq.co/docs/v2/forever/)
- [ ]
Portfolio
[Portfolio](https://dhanhq.co/docs/v2/portfolio/)
Table of contents


- [Holdings](https://dhanhq.co/docs/v2/portfolio/#holdings)
- [Positions](https://dhanhq.co/docs/v2/portfolio/#positions)
- [Convert Position](https://dhanhq.co/docs/v2/portfolio/#convert-position)

- [EDIS](https://dhanhq.co/docs/v2/edis/)
- [Trader's Control](https://dhanhq.co/docs/v2/traders-control/)
- [Funds](https://dhanhq.co/docs/v2/funds/)
- [Statement](https://dhanhq.co/docs/v2/statements/)
- [Postback](https://dhanhq.co/docs/v2/postback/)
- [Live Order Update](https://dhanhq.co/docs/v2/order-update/)

- [ ]
Data APIs

Data APIs


- [Market Quote](https://dhanhq.co/docs/v2/market-quote/)
- [Live Market Feed](https://dhanhq.co/docs/v2/live-market-feed/)
- [Full Market Depth](https://dhanhq.co/docs/v2/full-market-depth/)
- [Historical Data](https://dhanhq.co/docs/v2/historical-data/)
- [Expired Options Data](https://dhanhq.co/docs/v2/expired-options-data/)
- [Option Chain](https://dhanhq.co/docs/v2/option-chain/)

- [Annexure](https://dhanhq.co/docs/v2/annexure/)
- [Instrument List](https://dhanhq.co/docs/v2/instruments/)
- [Releases](https://dhanhq.co/docs/v2/releases/)

Table of contents


- [Holdings](https://dhanhq.co/docs/v2/portfolio/#holdings)
- [Positions](https://dhanhq.co/docs/v2/portfolio/#positions)
- [Convert Position](https://dhanhq.co/docs/v2/portfolio/#convert-position)

# Portfolio

This API lets you retrieve holdings and positions in your portfolio.

|     |     |     |
| --- | --- | --- |
| GET | /holdings | Retrieve list of holdings in demat account |
| GET | /positions | Retrieve open positions |
| POST | /positions/convert | Convert intraday position to delivery or delivery to intraday |

## Holdings

Users can retrieve all holdings bought/sold in previous trading sessions. All T1 and delivered quantities can be fetched.

```
    curl --request GET \
    --url https://api.dhan.co/v2/holdings \
    --header 'Content-Type: application/json' \
    --header 'access-token: JWT'
```

**Request Structure**

_No Body_

**Response Structure**

```
[\
    {\
    "exchange": "ALL",\
    "tradingSymbol": "HDFC",\
    "securityId": "1330",\
    "isin": "INE001A01036",\
    "totalQty": 1000,\
    "dpQty": 1000,\
    "t1Qty": 0,\
    "availableQty": 1000,\
    "collateralQty": 0,\
    "avgCostPrice": 2655.0\
    }\
]
```

**Parameters**

| Field | Type | Description |
| --- | --- | --- |
| exchange | enum string | Exchange |
| tradingSymbol | string | Refer Trading Symbol at Page No |
| securityId | string | Exchange standard ID for each scrip. Refer [here](https://dhanhq.co/docs/v2/instruments/) |
| isin | string | Universal standard ID for each scrip |
| totalQty | int | Total quantity |
| dpQty | int | Quantity delivered in demat account |
| t1Qty | int | Quantity pending delivered in demat account |
| availableQty | int | Quantity available for transaction |
| collateralQty | int | Quantity placed as collateral with broker |
| avgCostPrice | float | Average Buy Price of total quantity |

## Positions

Users can retrieve a list of all open positions for the day. This includes all F&O carryforward positions as well.

```
    curl --request GET \
    --url https://api.dhan.co/v2/positions \
    --header 'Content-Type: application/json' \
    --header 'access-token: JWT'
```

**Request Structure**

_No Body_

**Response Structure**

```
[\
    {\
    "dhanClientId": "1000000009",\
    "tradingSymbol": "TCS",\
    "securityId": "11536",\
    "positionType": "LONG",\
    "exchangeSegment": "NSE_EQ",\
    "productType": "CNC",\
    "buyAvg": 3345.8,\
    "buyQty": 40,\
    "costPrice": 3215.0,\
    "sellAvg": 0.0,\
    "sellQty": 0,\
    "netQty": 40,\
    "realizedProfit": 0.0,\
    "unrealizedProfit": 6122.0,\
    "rbiReferenceRate": 1.0,\
    "multiplier": 1,\
    "carryForwardBuyQty": 0,\
    "carryForwardSellQty": 0,\
    "carryForwardBuyValue": 0.0,\
    "carryForwardSellValue": 0.0,\
    "dayBuyQty": 40,\
    "daySellQty": 0,\
    "dayBuyValue": 133832.0,\
    "daySellValue": 0.0,\
    "drvExpiryDate": "0001-01-01",\
    "drvOptionType": null,\
    "drvStrikePrice": 0.0.\
    "crossCurrency": false\
    }\
]
```

**Parameters**

| Field | Type | Description |
| --- | --- | --- |
| dhanClientId | string | User specific identification generated by Dhan |
| tradingSymbol | string | Refer Trading Symbol in Tables |
| securityId | string | Exchange standard id for each scrip. Refer [here](https://dhanhq.co/docs/v2/instruments/) |
| positionType | enum string | Position Type <br>`LONG``SHORT``CLOSED` |
| exchangeSegment | enum string | Exchange & Segment<br>`NSE_EQ``NSE_FNO``NSE_CURRENCY``BSE_EQ``BSE_FNO``BSE_CURRENCY``MCX_COMM` |
| productType | enum string | Product type<br>`CNC``INTRADAY``MARGIN``MTF`` CO``BO` |
| buyAvg | float | Average buy price mark to market |
| buyQty | int | Total quantity bought |
| costPrice | int | Actual Cost Price |
| sellAvg | float | Average sell price mark to market |
| sellQty | int | Total quantities sold |
| netQty | int | buyQty - sellQty = netQty |
| realizedProfit | float | Profit or loss booked |
| unrealizedProfit | float | Profit or loss standing for open position |
| rbiReferenceRate | float | RBI mandated reference rate for forex |
| multiplier | int | Multiplying factor for currency F&O |
| carryForwardBuyQty | int | Carry forward F&O long quantities |
| carryForwardSellQty | int | Carry forward F&O short quantities |
| carryForwardBuyValue | float | Carry forward F&O long value |
| carryForwardSellValue | float | Carry forward F&O short value |
| dayBuyQty | int | Quantities bought today |
| daySellQty | int | Quantities sold today |
| dayBuyValue | float | Value of quantities bought today |
| daySellValue | float | Value of quantities sold today |
| drvExpiryDate | string | For F&O, expiry date of contract |
| drvOptionType | enum string | Type of Option<br>`CALL``PUT` |
| drvStrikePrice | float | For Options, Strike Price |
| crossCurrency | boolean | Check for non INR currency pair |

## Convert Position

Users can convert their open position from intraday to delivery or delivery to intraday.

```
curl --request POST \
--url https://api.dhan.co/v2/positions/convert \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--header 'access-token: JWT' \
--data '{}'
```

**Request Structure**

```
{
    "dhanClientId": "1000000009",
    "fromProductType":"INTRADAY",
    "exchangeSegment":"NSE_EQ",
    "positionType":"LONG",
    "securityId":"11536",
    "tradingSymbol":"",
    "convertQty":"40",
    "toProductType":"CNC"
}
```

**Parameters**

| Field | Type | Description |
| --- | --- | --- |
| dhanClientId | string | User specific identification generated by Dhan |
| fromProductType | enum string | Refer Trading Symbol in Tables<br>`CNC``INTRADAY``MARGIN``CO``BO` |
| exchangeSegment | enum string | Exchange & segment in which position is created - [here](https://dhanhq.co/docs/v2/annexure/#exchange-segment) |
| positionType | enum string | Position Type<br>`LONG``SHORT``CLOSED` |
| securityId | string | Exchange standard id for each scrip. Refer [here](https://dhanhq.co/docs/v2/instruments/) |
| tradingSymbol | string | Refer Trading Symbol in Tables |
| convertQty | int | No of shares modification is desired |
| toProductType | enum string | Desired product type<br>`CNC``INTRADAY``MARGIN``CO``BO` |

**Response Structure**

```
202 Accepted
```

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

09:35 AM

Need Help?