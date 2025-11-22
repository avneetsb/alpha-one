[Skip to content](https://dhanhq.co/docs/v2/full-market-depth/#full-market-depth)



You are on the latest version of DhanHQ API.



[![logo](https://dhanhq.co/docs/v2/img/DhanHQ_logo.svg)](https://dhanhq.co/ "Ver 2.0 / API Documentation")

Ver 2.0 / API Documentation



Full Market Depth



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
- [ ]
Full Market Depth
[Full Market Depth](https://dhanhq.co/docs/v2/full-market-depth/)
Table of contents


- [Establishing Connection](https://dhanhq.co/docs/v2/full-market-depth/#establishing-connection)

- [20 Level](https://dhanhq.co/docs/v2/full-market-depth/#20-level)
- [200 Level](https://dhanhq.co/docs/v2/full-market-depth/#200-level)

- [Adding Instruments](https://dhanhq.co/docs/v2/full-market-depth/#adding-instruments)

- [20 Level](https://dhanhq.co/docs/v2/full-market-depth/#20-level_1)
- [200 Level](https://dhanhq.co/docs/v2/full-market-depth/#200-level_1)

- [Keeping Connection Alive](https://dhanhq.co/docs/v2/full-market-depth/#keeping-connection-alive)
- [Response Structure](https://dhanhq.co/docs/v2/full-market-depth/#response-structure)

- [20 Level](https://dhanhq.co/docs/v2/full-market-depth/#20-level_2)

- [Response Header](https://dhanhq.co/docs/v2/full-market-depth/#response-header)
- [Depth Packet](https://dhanhq.co/docs/v2/full-market-depth/#depth-packet)

- [200 Level](https://dhanhq.co/docs/v2/full-market-depth/#200-level_2)

- [Response Header](https://dhanhq.co/docs/v2/full-market-depth/#response-header_1)
- [Depth Packet](https://dhanhq.co/docs/v2/full-market-depth/#depth-packet_1)

- [Feed Disconnect](https://dhanhq.co/docs/v2/full-market-depth/#feed-disconnect)

- [Historical Data](https://dhanhq.co/docs/v2/historical-data/)
- [Expired Options Data](https://dhanhq.co/docs/v2/expired-options-data/)
- [Option Chain](https://dhanhq.co/docs/v2/option-chain/)

- [Annexure](https://dhanhq.co/docs/v2/annexure/)
- [Instrument List](https://dhanhq.co/docs/v2/instruments/)
- [Releases](https://dhanhq.co/docs/v2/releases/)

Table of contents


- [Establishing Connection](https://dhanhq.co/docs/v2/full-market-depth/#establishing-connection)

- [20 Level](https://dhanhq.co/docs/v2/full-market-depth/#20-level)
- [200 Level](https://dhanhq.co/docs/v2/full-market-depth/#200-level)

- [Adding Instruments](https://dhanhq.co/docs/v2/full-market-depth/#adding-instruments)

- [20 Level](https://dhanhq.co/docs/v2/full-market-depth/#20-level_1)
- [200 Level](https://dhanhq.co/docs/v2/full-market-depth/#200-level_1)

- [Keeping Connection Alive](https://dhanhq.co/docs/v2/full-market-depth/#keeping-connection-alive)
- [Response Structure](https://dhanhq.co/docs/v2/full-market-depth/#response-structure)

- [20 Level](https://dhanhq.co/docs/v2/full-market-depth/#20-level_2)

- [Response Header](https://dhanhq.co/docs/v2/full-market-depth/#response-header)
- [Depth Packet](https://dhanhq.co/docs/v2/full-market-depth/#depth-packet)

- [200 Level](https://dhanhq.co/docs/v2/full-market-depth/#200-level_2)

- [Response Header](https://dhanhq.co/docs/v2/full-market-depth/#response-header_1)
- [Depth Packet](https://dhanhq.co/docs/v2/full-market-depth/#depth-packet_1)

- [Feed Disconnect](https://dhanhq.co/docs/v2/full-market-depth/#feed-disconnect)

# Full Market Depth

Level 3 data includes market depth upto 20 levels. We are extending beyond and adding 200 level data. This shows complete picture of the market movements and it is streamed real-time via websockets.

This data can be used to detect demand supply zones, outside of 5 level market depth and build trading systems to detect market movements.

> Only NSE Equity and Derivatives segments are enabled for Full Market Depth.

Similar to [Live Market Feed](https://dhanhq.co/docs/v2/live-market-feed), all request messages over WebSocket are in JSON whereas all response messages over WebSocket are in Binary.

## Establishing Connection

### 20 Level

To establish connection with DhanHQ WebSocket for 20 Level Market Depth, you can connect to the below endpoint using WebSocket library.

```
wss://depth-api-feed.dhan.co/twentydepth?token=eyxxxxx&clientId=100xxxxxxx&authType=2
```

### 200 Level

To establish connection with DhanHQ WebSocket for 200 Level Market Depth, you can connect to the below endpoint using WebSocket library.

```
wss://full-depth-api.dhan.co/twohundreddepth?token=eyxxxxx&clientId=100xxxxxxx&authType=2
```

**Query Parameters**

| Field | Description |
| --- | --- |
| token<br>_required_ | Access Token generated via Dhan |
| clientId<br>_required_ | User specific identification generated by Dhan |
| authType<br>_required_ | `2` by Default |

## Adding Instruments

### 20 Level

For 20 Level Market Depth, you can subscribe upto 50 instruments in a single connection and receive market data packets.

For subscribing, this can be done using JSON message which needs to be sent over WebSocket connection.

Note

You can send all 50 instruments in a single JSON message for 20 Depth. You can send multiple messages over a single connection as well to subscribe to all instruments in parts and receive data.

**Request Structure**

```
{
    "RequestCode" : 23,
    "InstrumentCount" : 1,
    "InstrumentList" : [\
        {\
            "ExchangeSegment" : "NSE_EQ",\
            "SecurityId" : "1333"\
        }\
    ]
}
```

**Parameters**

| Field | Type | Description |
| --- | --- | --- |
| RequestCode<br>_required_ | int | Code for subscribing to particular data mode. `23` for Full Market Depth.<br> <br>Refer to [feed request code](https://dhanhq.co/docs/v2/annexure/#feed-request-code) to subscribe to required data mode |
| InstrumentCount<br>_required_ | int | No. of instruments to subscribe from this request |
| InstrumentList.ExchangeSegment<br>_required_ | enum string | Exchange Segment of instrument to be subscribed as found in [Annexure](https://dhanhq.co/docs/v2/annexure/#exchange-segment) |
| InstrumentList.SecurityId<br>_required_ | string | Exchange standard ID for each scrip. Refer [here](https://dhanhq.co/docs/v2/instruments/) |

### 200 Level

In 200 level market depth, only 1 instrument per connection can be subscribed. The JSON payload needs to be sent similar to 20 level depth subscription, while the socket connection has been established.

**Request Structure**

```
{
    "RequestCode" : 23,
    "ExchangeSegment" : "NSE_EQ",
    "SecurityId" : "1333"
}
```

**Parameters**

| Field | Type | Description |
| --- | --- | --- |
| RequestCode<br>_required_ | int | Code for subscribing to particular data mode. `23` for Full Market Depth.<br> <br>Refer to [feed request code](https://dhanhq.co/docs/v2/annexure/#feed-request-code) to subscribe to required data mode |
| ExchangeSegment<br>_required_ | enum string | Exchange Segment of instrument to be subscribed as found in [Annexure](https://dhanhq.co/docs/v2/annexure/#exchange-segment) |
| SecurityId<br>_required_ | string | Exchange standard ID for each scrip. Refer [here](https://dhanhq.co/docs/v2/instruments/) |

## Keeping Connection Alive

To keep the WebSocket connection alive and prevent it from closing, the server side uses **Ping-Pong** module. Server side sends ping every 10 seconds to the client server (in this case, your system) to maintain WebSocket status as open.

An automated pong is sent by websocket library. You can use the same as response to the ping.

> In case the client server does not respond for more than 40 seconds, the connection is closed from server side and you will have to reestablish connection.

## Response Structure

The market depth data is sent as structured binary packet. It will require parsing to readable format to extract the relevant information.

All responses from Dhan Market Feed consists of [Response Header](https://dhanhq.co/docs/v2/full-market-depth/#response-header) and Payload. Header for every response message remains the same with different [feed response code](https://dhanhq.co/docs/v2/annexure/#feed-response-code), while the payload can be different.

### 20 Level

#### Response Header

The response header message is of 12 bytes which will remain as part of the response message. The message structure is given as below.

| Bytes | Type | Size | Description |
| --- | --- | --- | --- |
| `1-2` | int16 | `2` | Message Length of the entire payload packet |
| `3` | \[ \] byte | `1` | Feed Response Code can be referred in [Annexure](https://dhanhq.co/docs/v2/annexure/#feed-response-code) |
| `4` | \[ \] byte | `1` | Exchange Segment can be referred in [Annexure](https://dhanhq.co/docs/v2/annexure/#exchange-segment) |
| `5-8` | int32 | `4` | Security ID - can be found [here](https://dhanhq.co/docs/v2/instruments/) |
| `9-12` | uint32 | `4` | Message Sequence (to be ignored) |

#### Depth Packet

Depth Data Packet for 20 level market depth is structured differently from 5 level depth. Over here, you will receive the bid (sell) and ask (buy) data packets separately, each containing 20 packets of 16 bytes each.

| Bytes | Type | Size | Description |
| --- | --- | --- | --- |
| `0-12` | \[ \] array | `12` | [Response Header](https://dhanhq.co/docs/v2/full-market-depth/#response-header)<br>`41` for Bid Data (Buy)<br> <br>`51` for Ask Data (Sell)<br> <br>Refer to [enum](https://dhanhq.co/docs/v2/annexure/#feed-response-code) for values |
| `13-332` | Bid/Ask Depth Structure | `320` | 20 packets of 16 bytes each for each instrument in below provided structure |

Each of these 20 packets will be received in the following packet structure:

| Bytes | Type | Size | Description |
| --- | --- | --- | --- |
| `1-8` | float64 | `8` | Price |
| `9-12` | uint32 | `4` | Quantity |
| `13-16` | uint32 | `4` | No. of Orders |

### 200 Level

#### Response Header

The response header message is of 12 bytes which will remain as part of the response message. The message structure is given as below.

| Bytes | Type | Size | Description |
| --- | --- | --- | --- |
| `1-2` | int16 | `2` | Message Length of the entire payload packet |
| `3` | \[ \] byte | `1` | Feed Response Code can be referred in [Annexure](https://dhanhq.co/docs/v2/annexure/#feed-response-code) |
| `4` | \[ \] byte | `1` | Exchange Segment can be referred in [Annexure](https://dhanhq.co/docs/v2/annexure/#exchange-segment) |
| `5-8` | int32 | `4` | Security ID - can be found [here](https://dhanhq.co/docs/v2/instruments/) |
| `9-12` | uint32 | `4` | No of Rows - gives number of rows to be read for response |

#### Depth Packet

200 level market depth is structured similar to 20 level depth. Over here, you will receive the bid (sell) and ask (buy) data packets separately, each containing multiple packets of 16 bytes each.

| Bytes | Type | Size | Description |
| --- | --- | --- | --- |
| `0-12` | \[ \] array | `12` | [Response Header](https://dhanhq.co/docs/v2/full-market-depth/#response-header)<br>`41` for Bid Data (Buy)<br> <br>`51` for Ask Data (Sell)<br> <br>Refer to [enum](https://dhanhq.co/docs/v2/annexure/#feed-response-code) for values |
| `13-3212` | Bid/Ask Depth Structure | `3200` | 200 packets of 16 bytes each for each instrument in below provided structure |

Each of these 200 packets will be received in the following packet structure:

| Bytes | Type | Size | Description |
| --- | --- | --- | --- |
| `1-8` | float64 | `8` | Price |
| `9-12` | uint32 | `4` | Quantity |
| `13-16` | uint32 | `4` | No. of Orders |

Note

Whenever 20 or 200 level depth packets are sent on the connection, they are stacked one after another in a single message.
For 20 level depth, if two instruments are subscribed, then the first instrument's Bid packet followed by Ask packet of that instrument
is added and then the second instrument's bid and ask packets in same sequence.
To handle this, you can break down the packet on the basis of length.

## Feed Disconnect

If you want to disconnect WebSocket, you can send below JSON request message via the connection.

```
{
    "RequestCode" : 12
}
```

In case of WebSocket disconnection from server side, you will receive disconnection packet, which will have disconnection reason code.

- If more than 5 websockets are established, then the first socket will be disconnected with `805` with every additional connection.

| Bytes | Type | Size | Description |
| --- | --- | --- | --- |
| `0-12` | \[ \] array | `8` | [Response Header](https://dhanhq.co/docs/v2/full-market-depth/#request-header) with code `50`<br>Refer to [enum](https://dhanhq.co/docs/v2/annexure/#feed-response-code) for values |
| `13-14` | int16 | `2` | Disconnection message code - [here](https://dhanhq.co/docs/v2/annexure/#data-api-error) |

You can find detailed Disconnection message code description [here](https://dhanhq.co/docs/v2/annexure/#data-api-error).

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