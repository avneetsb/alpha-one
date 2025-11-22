[Skip to content](https://dhanhq.co/docs/v2/annexure/#annexure)



You are on the latest version of DhanHQ API.



[![logo](https://dhanhq.co/docs/v2/img/DhanHQ_logo.svg)](https://dhanhq.co/ "Ver 2.0 / API Documentation")

Ver 2.0 / API Documentation



Annexure



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

- [ ]
Data APIs

Data APIs


- [Market Quote](https://dhanhq.co/docs/v2/market-quote/)
- [Live Market Feed](https://dhanhq.co/docs/v2/live-market-feed/)
- [Full Market Depth](https://dhanhq.co/docs/v2/full-market-depth/)
- [Historical Data](https://dhanhq.co/docs/v2/historical-data/)
- [Expired Options Data](https://dhanhq.co/docs/v2/expired-options-data/)
- [Option Chain](https://dhanhq.co/docs/v2/option-chain/)

- [ ]
Annexure
[Annexure](https://dhanhq.co/docs/v2/annexure/)
Table of contents


- [Exchange Segment](https://dhanhq.co/docs/v2/annexure/#exchange-segment)
- [Product Type](https://dhanhq.co/docs/v2/annexure/#product-type)
- [Order Status](https://dhanhq.co/docs/v2/annexure/#order-status)
- [After Market Order time](https://dhanhq.co/docs/v2/annexure/#after-market-order-time)
- [Expiry Code](https://dhanhq.co/docs/v2/annexure/#expiry-code)
- [Instrument](https://dhanhq.co/docs/v2/annexure/#instrument)
- [Feed Request Code](https://dhanhq.co/docs/v2/annexure/#feed-request-code)
- [Feed Response Code](https://dhanhq.co/docs/v2/annexure/#feed-response-code)
- [Trading API Error](https://dhanhq.co/docs/v2/annexure/#trading-api-error)
- [Data API Error](https://dhanhq.co/docs/v2/annexure/#data-api-error)

- [Instrument List](https://dhanhq.co/docs/v2/instruments/)
- [Releases](https://dhanhq.co/docs/v2/releases/)

Table of contents


- [Exchange Segment](https://dhanhq.co/docs/v2/annexure/#exchange-segment)
- [Product Type](https://dhanhq.co/docs/v2/annexure/#product-type)
- [Order Status](https://dhanhq.co/docs/v2/annexure/#order-status)
- [After Market Order time](https://dhanhq.co/docs/v2/annexure/#after-market-order-time)
- [Expiry Code](https://dhanhq.co/docs/v2/annexure/#expiry-code)
- [Instrument](https://dhanhq.co/docs/v2/annexure/#instrument)
- [Feed Request Code](https://dhanhq.co/docs/v2/annexure/#feed-request-code)
- [Feed Response Code](https://dhanhq.co/docs/v2/annexure/#feed-response-code)
- [Trading API Error](https://dhanhq.co/docs/v2/annexure/#trading-api-error)
- [Data API Error](https://dhanhq.co/docs/v2/annexure/#data-api-error)

# Annexure

## Exchange Segment

| Attribute | Exchange | Segment | enum |
| --- | --- | --- | --- |
| IDX\_I | Index | Index Value | `0` |
| NSE\_EQ | NSE | Equity Cash | `1` |
| NSE\_FNO | NSE | Futures & Options | `2` |
| NSE\_CURRENCY | NSE | Currency | `3` |
| BSE\_EQ | BSE | Equity Cash | `4` |
| MCX\_COMM | MCX | Commodity | `5` |
| BSE\_CURRENCY | BSE | Currency | `7` |
| BSE\_FNO | BSE | Futures & Options | `8` |

## Product Type

CO & BO product types will be valid only for Intraday.

| Attribute | Detail |
| --- | --- |
| CNC | Cash & Carry for equity deliveries |
| INTRADAY | Intraday for Equity, Futures & Options |
| MARGIN | Carry Forward in Futures & Options |
| CO | Cover Order |
| BO | Bracket Order |

## Order Status

| Attribute | Detail |
| --- | --- |
| TRANSIT | Did not reach the exchange server |
| PENDING | Awaiting execution |
| CLOSED | Used for Super Order, once both the entry and exit orders are placed |
| TRIGGERED | Used for Super Order, if Target or Stop Loss leg is triggered |
| REJECTED | Rejected by broker/exchange |
| CANCELLED | Cancelled by user |
| PART\_TRADED | Partial Quantity traded successfully |
| TRADED | Executed successfully |

## After Market Order time

| Attribute | Detail |
| --- | --- |
| PRE\_OPEN | AMO pumped at pre-market session |
| OPEN | AMO pumped at market open |
| OPEN\_30 | AMO pumped 30 minutes after market open |
| OPEN\_60 | AMO pumped 60 minutes after market open |

## Expiry Code

| Attribute | Detail |
| --- | --- |
| 0 | Current Expiry/Near Expiry |
| 1 | Next Expiry |
| 2 | Far Expiry |

## Instrument

| Attribute | Detail |
| --- | --- |
| INDEX | Index |
| FUTIDX | Futures of Index |
| OPTIDX | Options of Index |
| EQUITY | Equity |
| FUTSTK | Futures of Stock |
| OPTSTK | Options of Stock |
| FUTCOM | Futures of Commodity |
| OPTFUT | Options of Commodity Futures |
| FUTCUR | Futures of Currency |
| OPTCUR | Options of Currency |

## Feed Request Code

| Attribute | Detail |
| --- | --- |
| `11` | Connect Feed |
| `12` | Disconnect Feed |
| `15` | Subscribe - Ticker Packet |
| `16` | Unsubscribe - Ticker Packet |
| `17` | Subscribe - Quote Packet |
| `18` | Unsubscribe - Quote Packet |
| `21` | Subscribe - Full Packet |
| `22` | Unsubscribe - Full Packet |
| `23` | Subscribe - Full Market Depth |
| `24` | Unsubscribe - Full Market Depth |

## Feed Response Code

| Attribute | Detail |
| --- | --- |
| `1` | Index Packet |
| `2` | Ticker Packet |
| `4` | Quote Packet |
| `5` | OI Packet |
| `6` | Prev Close Packet |
| `7` | Market Status Packet |
| `8` | Full Packet |
| `50` | Feed Disconnect |

## Trading API Error

| Type | Code | Message |
| --- | --- | --- |
| Invalid Authentication | `DH-901` | Client ID or user generated access token is invalid or expired. |
| Invalid Access | `DH-902` | User has not subscribed to Data APIs or does not have access to Trading APIs. Kindly subscribe to Data APIs to be able to fetch Data. |
| User Account | `DH-903` | Errors related to User's Account. Check if the required segments are activated or other requirements are met. |
| Rate Limit | `DH-904` | Too many requests on server from single user breaching rate limits. Try throttling API calls. |
| Input Exception | `DH-905` | Missing required fields, bad values for parameters etc. |
| Order Error | `DH-906` | Incorrect request for order and cannot be processed. |
| Data Error | `DH-907` | System is unable to fetch data due to incorrect parameters or no data present. |
| Internal Server Error | `DH-908` | Server was not able to process API request. This will only occur rarely. |
| Network Error | `DH-909` | Network error where the API was unable to communicate with the backend system. |
| Others | `DH-910` | Error originating from other reasons. |

## Data API Error

| Code | Description |
| --- | --- |
| `800` | Internal Server Error |
| `804` | Requested number of instruments exceeds limit |
| `805` | Too many requests or connections. Further requests may result in the user being blocked. |
| `806` | Data APIs not subscribed |
| `807` | Access token is expired |
| `808` | Authentication Failed - Client ID or Access Token invalid |
| `809` | Access token is invalid |
| `810` | Client ID is invalid |
| `811` | Invalid Expiry Date |
| `812` | Invalid Date Format |
| `813` | Invalid SecurityId |
| `814` | Invalid Request |

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