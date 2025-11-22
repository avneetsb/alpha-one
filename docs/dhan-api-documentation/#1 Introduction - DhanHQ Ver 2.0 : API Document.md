[Skip to content](https://dhanhq.co/docs/v2/#introduction)



You are on the latest version of DhanHQ API.



[![logo](https://dhanhq.co/docs/v2/img/DhanHQ_logo.svg)](https://dhanhq.co/ "Ver 2.0 / API Documentation")

Ver 2.0 / API Documentation



Introduction



Type to start searching

[![logo](https://dhanhq.co/docs/v2/img/DhanHQ_logo.svg)](https://dhanhq.co/ "Ver 2.0 / API Documentation")
Ver 2.0 / API Documentation


- [ ]
Introduction
[Introduction](https://dhanhq.co/docs/v2/)
Table of contents


- [Getting Started](https://dhanhq.co/docs/v2/#getting-started)
- [Structure](https://dhanhq.co/docs/v2/#structure)
- [Errors](https://dhanhq.co/docs/v2/#errors)
- [Rate Limit](https://dhanhq.co/docs/v2/#rate-limit)

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
- [Releases](https://dhanhq.co/docs/v2/releases/)

Table of contents


- [Getting Started](https://dhanhq.co/docs/v2/#getting-started)
- [Structure](https://dhanhq.co/docs/v2/#structure)
- [Errors](https://dhanhq.co/docs/v2/#errors)
- [Rate Limit](https://dhanhq.co/docs/v2/#rate-limit)

# Introduction

## Getting Started

![alt](https://dhanhq.co/docs/v2/img/intro.svg)

DhanHQ API is a state-of-the-art platform for you to build trading and investment services & strategies.

It is a set of REST-like APIs that provide integration with our trading platform. Execute & modify orders in real time,
manage portfolio, access live market data and more, with lightning fast API collection.

We offer resource-based URLs that accept JSON or form-encoded requests. The response is returned as
JSON-encoded responses by using Standard HTTP response codes, verbs, and authentication.

![sandbox](https://dhanhq.co/docs/v2/img/btn2sandbox.png)

Developer Kit

[Explore Now\\
![sandbox](https://dhanhq.co/docs/v2/img/dhanhq-arrow.png)](https://api.dhan.co/v2/#)

![sandbox](https://dhanhq.co/docs/v2/img/btn2sandbox.png)

Developer Kit

[Explore Now](https://api.dhan.co/v2/#)![sandbox](https://dhanhq.co/docs/v2/img/dhanhq-arrow.png)

![python](https://dhanhq.co/docs/v2/img/btn2pydhanhq.png)

DhanHQ Python Client

[Explore Now\\
![python](https://dhanhq.co/docs/v2/img/dhanhq-arrow.png)](https://pypi.org/project/dhanhq/)

![python](https://dhanhq.co/docs/v2/img/btn2pydhanhq.png)

DhanHQ Python Client

[Explore Now](https://pypi.org/project/dhanhq/)![python](https://dhanhq.co/docs/v2/img/dhanhq-arrow.png)

## Structure

RESTPython

All GET and DELETE request parameters go as query parameters, and POST and PUT parameters as form-encoded.
User has to input an access token in the header for every request.

```
curl --request POST \
--url https://api.dhan.co/v2/ \
--header 'Content-Type: application/json' \
--header 'access-token: JWT' \
--data '{Request JSON}'
```

Install Python Package directly using following command in command line.

```
pip install dhanhq
```

This installs entire DhanHQ Python Client along with the required packages. Now, you can start using DhanHQ Client with your Python script.

You can now import 'dhanhq' module and connect to your Dhan account.

```
from dhanhq import dhanhq

dhan = dhanhq("client_id","access_token")
```

## Errors

Error responses come with the error code and message generated internally by the system. The sample structure of
error response is shown below.

```
{
    "errorType": "",
    "errorCode": "",
    "errorMessage": ""
}
```

You can find detailed error code and message under [Annexure](https://dhanhq.co/docs/v2/annexure/#trading-api-error).

## Rate Limit

| Rate Limit | Order APIs | Data APIs | Quote APIs | Non Trading APIs |
| --- | --- | --- | --- | --- |
| per second | 25 | 5 | 1 | 20 |
| per minute | 250 | - | Unlimited | Unlimited |
| per hour | 1000 | - | Unlimited | Unlimited |
| per day | 7000 | 100000 | Unlimited | Unlimited |

Order Modifications are capped at 25 modifications/order

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