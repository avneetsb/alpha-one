[Skip to content](https://dhanhq.co/docs/v2/releases/#release-notes)



You are on the latest version of DhanHQ API.



[![logo](https://dhanhq.co/docs/v2/img/DhanHQ_logo.svg)](https://dhanhq.co/ "Ver 2.0 / API Documentation")

Ver 2.0 / API Documentation



Releases



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

- [Annexure](https://dhanhq.co/docs/v2/annexure/)
- [Instrument List](https://dhanhq.co/docs/v2/instruments/)
- [ ]
Releases
[Releases](https://dhanhq.co/docs/v2/releases/)
Table of contents


- [Version 2.4](https://dhanhq.co/docs/v2/releases/#version-24)

- [New Features](https://dhanhq.co/docs/v2/releases/#new-features)
- [Breaking Changes](https://dhanhq.co/docs/v2/releases/#breaking-changes)

- [Version 2.3](https://dhanhq.co/docs/v2/releases/#version-23)

- [New Features](https://dhanhq.co/docs/v2/releases/#new-features_1)

- [Version 2.2](https://dhanhq.co/docs/v2/releases/#version-22)

- [New Features](https://dhanhq.co/docs/v2/releases/#new-features_2)
- [Improvements](https://dhanhq.co/docs/v2/releases/#improvements)
- [Breaking Changes](https://dhanhq.co/docs/v2/releases/#breaking-changes_1)

- [Version 2.1](https://dhanhq.co/docs/v2/releases/#version-21)

- [New Features](https://dhanhq.co/docs/v2/releases/#new-features_3)
- [Improvements](https://dhanhq.co/docs/v2/releases/#improvements_1)

- [Version 2](https://dhanhq.co/docs/v2/releases/#version-2)

- [New Features](https://dhanhq.co/docs/v2/releases/#new-features_4)
- [Improvements](https://dhanhq.co/docs/v2/releases/#improvements_2)
- [Breaking Changes](https://dhanhq.co/docs/v2/releases/#breaking-changes_2)
- [Bug Fixes](https://dhanhq.co/docs/v2/releases/#bug-fixes)

Table of contents


- [Version 2.4](https://dhanhq.co/docs/v2/releases/#version-24)

- [New Features](https://dhanhq.co/docs/v2/releases/#new-features)
- [Breaking Changes](https://dhanhq.co/docs/v2/releases/#breaking-changes)

- [Version 2.3](https://dhanhq.co/docs/v2/releases/#version-23)

- [New Features](https://dhanhq.co/docs/v2/releases/#new-features_1)

- [Version 2.2](https://dhanhq.co/docs/v2/releases/#version-22)

- [New Features](https://dhanhq.co/docs/v2/releases/#new-features_2)
- [Improvements](https://dhanhq.co/docs/v2/releases/#improvements)
- [Breaking Changes](https://dhanhq.co/docs/v2/releases/#breaking-changes_1)

- [Version 2.1](https://dhanhq.co/docs/v2/releases/#version-21)

- [New Features](https://dhanhq.co/docs/v2/releases/#new-features_3)
- [Improvements](https://dhanhq.co/docs/v2/releases/#improvements_1)

- [Version 2](https://dhanhq.co/docs/v2/releases/#version-2)

- [New Features](https://dhanhq.co/docs/v2/releases/#new-features_4)
- [Improvements](https://dhanhq.co/docs/v2/releases/#improvements_2)
- [Breaking Changes](https://dhanhq.co/docs/v2/releases/#breaking-changes_2)
- [Bug Fixes](https://dhanhq.co/docs/v2/releases/#bug-fixes)

# Release Notes

## Version 2.4

_Date: Monday Sep 22 2025_

In line with the changes in SEBI guidelines on Retail Participation in Algorithmic Trading, we are bringing changes to the authentication module - primarily in 3 areas - reduced Access Token duration, API Key based authentication and IP setup.

### New Features

- **API Key based login**


We have introduced a new authentication module for accessing APIs - API key and secret for individuals. You can now use this method to generate API key for one year validity and generate new access token daily by verifying your Dhan credentials. You can read more about the same [here](https://dhanhq.co/docs/v2/authentication/#api-key-secret).

### Breaking Changes

- **Access Token can only be generated for 24 hours**


Access token based authentication will only be allowed for 24 hours after generating token. This is in line with the exchange and SEBI guidelines on management of API access.

- **Static IP Requirement**


Static IP is required for all Order APIs for placing, modifying and cancelling any type of orders - including normal orders, super orders and forever orders. You can setup IP directly via API as per the [.Setup Static IP APIs](https://dhanhq.co/docs/v2/authentication/#setup-static-ip).


## Version 2.3

_Date: Monday Sep 08 2025_

We are adding one of the most requested data on APIs - 200 Market Depth on Websockets and historical expired options contract data. Along with this, we are changing rate limit for Order APIs to 10 order per second, in accordance with regulations.

### New Features

- **Full Market Depth**


You can get depth upto 200 levels directly on websocket - an extension of 20 Market Depth with more data so you can design trading system on top of more data and find our opportunity zones seamlessly. You can read more about how this will work [here](https://dhanhq.co/docs/v2/full-market-depth/#200-level).

- **Historical Options Data**


This endpoint is built for you to fetch options data of expired contracts, on a rolling basis. You do not need to look for the security ID for expired contracts, rather you can enter strikes as ATM +/- to fetch data. You can also get rolling IV, OI and volumes directly from [here](https://dhanhq.co/docs/v2/expired-options-data).


## Version 2.2

_Date: Friday Mar 07 2025_

We are adding a new order type on Dhan and which is available on v2 of DhanHQ API. This order type is called Super Order. This along with a major update to Historical Data APIs is added. You can now fetch upto last 5 years of Intraday Historical Data (minutewise) and also OI data for futures and options instruments.

### New Features

- **Super Orders**


Super Orders are a new order type which allows you to combine multiple orders for entry and exit into single order. You can enter a position and place target and stop loss orders for the same along with the option to trail your stop loss. This combines the benefits of a bracket order and a trailing stop, and is available across all exchanges and segments - [Super Order](https://dhanhq.co/docs/v2/super-order).

- **User Profile**


User Profile API is built to give a status check about different information related to user's account. This includes token validity, active segments, Data API subscription status and validity, and different user configurations like DDPI status and MTF enablement - [here](https://dhanhq.co/docs/v2/#user-profile).


### Improvements

- **Intraday Historical Data**


Intraday Historical Data is now available for last 5 years of data. This is available for all NSE, BSE and MCX instruments. Along with increase in time range, we have also added OI data for futures and options instruments. There is `oi` parameter added to the API. Also, the `from_date` and `to_date` has option to pass IST time as well to fetch particular number of bars only. You can head over to documentation for updates in fields - [here](https://dhanhq.co/docs/v2/historical-data/#intraday-historical-data).

- **Daily Historical Data**


Daily Historical Data has added OI data for futures and options instruments. There is `oi` parameter added to the API which is _optional_ and can be used to fetch OI data - [here](https://dhanhq.co/docs/v2/historical-data/#daily-historical-data).

- **`CorrelationId` on Live Order Update**


Live Order Update now has two additional keys called 'CorrelationId' and 'Remarks' - [here](https://dhanhq.co/docs/v2/order-update/#order-update).


### Breaking Changes

- **Changes in Rate Limit**


Rate limits have been increased for Data APIs which includes Historical Data. There are no rate limits on minute and hourly time frames. You can request upto 1,00,000 requests in a day and seconds timeframe are limited to 5 requests per second - [Rate Limit](https://dhanhq.co/docs/v2/#rate-limits).

## Version 2.1

_Date: Monday Jan 06 2025_

This add-on to DhanHQ v2 comes with 20 level market depth (Level 3 data) for APIs. Along with that, this also covers Option Chain API, which was released in between and smaller enhancements.

### New Features

- **20 Market Depth**


You can get real-time streaming of 20 level market depth, for all NSE instruments with [20 Market Depth](https://dhanhq.co/docs/v2/full-market-depth). It is delivered via websockets and can be used to detect demand-supply zones and build your systems on top of it.

- **Option Chain**


Dhan's Advanced Option Chain is made available on a single API request, for any underlying. With this, you get OI, greeks, volume, top bid/ask and price data of all strikes of any Option Instrument, across exchanges and segments - for NSE, BSE and MCX traded options - [Option Chain API](https://dhanhq.co/docs/v2/option-chain).


### Improvements

- **'expiryCode' in Daily Historical Data**


Daily Historical Data now has expiryCode as an _"Optional"_ field - [Daily Historical Data API](https://dhanhq.co/docs/v2/historical-data/#daily-historical-data).

## Version 2

_Date: Monday Sep 15 2024_

DhanHQ v2 extends execution capability with live order updates and forever orders on superfast APIs. Along with this, we also released Market Quote APIs, built on top of Live Market Feed which can be integrated with ease. We have also introduced improvements, bug fixes and increased stability with new version.

### New Features

- **Market Quote**


Fetch LTP, Quote (with OI) and Market Depth data directly on API, for upto 1000 instruments at once with [Market Quote API](https://dhanhq.co/docs/v2/market-quote).

- **Forever Orders**


Place, modify and manage your Forever Orders, including single and OCO orders to manage risk and trade efficiently with [Forever Order API](https://dhanhq.co/docs/v2/forever).

- **Live Order Update**


Order Updates are sent in real time via websockets, which will update order status of all your orders placed via any platform - [Live Order Update](https://dhanhq.co/docs/v2/order-update).

- **Margin Calculator**


Margin Calculation simplifies order placement by providing details about required margin and available balances before placing order - [Margin Calculator API](https://dhanhq.co/docs/v2/funds/#margin-calculator).


### Improvements

- **Intraday Historical Data**


Intraday Historical Data now provides OHLC with Volume data for last 5 trading days across timeframes such as 1 min, 5 min, 15 min, 25 min and 60 min - [Intraday Historical Data API](https://dhanhq.co/docs/v2/historical-data/#intraday-historical-data).

- **GET Order APIs**

`filledQty`, `remainingQuantity` and `averageTradedPrice` is available as part of all GET Order APIs, which makes it simpler to fetch post execution details of an order. We have also added `PART_TRADED` as a flag on `orderStatus` which will be clear distinction for partially traded orders.

- **Live Market Feed**


You can now authorise [Live Market Feed](https://dhanhq.co/docs/v2/live-market-feed/) via Query Parameters and subscribe/unsubscribe to instruments via JSON messages on websockets with this version. Also, `FULL` packet is now available which will gives LTP, Quote, OI and Market Depth data in a single packet.


### Breaking Changes

- **Order Placement**


Deprecated non-mandatory request keys including `tradingSymbol`, `drvExpiryDate`, `drvOptionType` and `drvStrikePrice` from Order Placement API endpoints. Along with this, pre-open AMO orders can also be placed now with `PRE_OPEN` tag.

- **Order Modification**

`quantity` field needs to be placed order quantity instead of pending order quantity. Earlier, for Order Modification API, in case of partial execution, user needed to pass pending order quantity, which led to errors due to simultaneous execution on exchange or need to call GET Trade APIs as well.
`quantity` and `price` fields are conditionally required for modification.



`quantity` field in Order Modification

- **Daily Historical Data**

`symbol` is replaced with `securityId` as key in Daily Historical Data, making it simple for users to fetch data everywhere with Security ID itself - [Daily Historical Data API](https://dhanhq.co/docs/v2/historical-data/#daily-historical-data).

- **Error Messages**


Now error messages are categorised with DH-900 series which helps you self debug on level of error - [Error Codes](https://dhanhq.co/docs/v2/#errors).

- **Security ID List Mapping**


Security ID List is now comprehensive with tag changes as well. Check new mappings and description - [Security ID List](https://dhanhq.co/docs/v2/instruments).

- **Epoch time introduced instead of Julian time in Historical Data APIs** \-
Timestamp in [Daily Historical Data API](https://dhanhq.co/docs/v2/historical-data/#daily-historical-data) and [Intraday Historical Data API](https://dhanhq.co/docs/v2/historical-data/#intraday-historical-data) is now Epoch or UNIX time and with key `timestamp`.

- **`Market Depth` deprecated as mode in Live Market Feed**

`Market Depth` mode is now replaced with `FULL` packet which has combined data of Quote, OI and market depth in single packet, enabling ease in subscribing and fetching data.

- **Change in endpoint for Trade History and Kill Switch**


New endpoint for Trade History is `/trades`, making it common with other Trade book APIs. For Kill Switch, the new endpoint as per nomenclature is `killswitch`.


### Bug Fixes

- **`realizedProfit` and `unrealizedProfit` in Positions API**


You can now get realtime values of `realizedProfit` and `unrealizedProfit` on [Positions API](https://dhanhq.co/docs/v2/portfolio/#positions).

- **Target leg modification in Order Modification API**

`TARGET_LEG` was not getting modified with [Order Modification API](https://dhanhq.co/docs/v2/orders/#order-modification) which is fixed now.


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