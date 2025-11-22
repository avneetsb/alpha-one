[Skip to content](https://dhanhq.co/docs/v2/edis/#edis)



You are on the latest version of DhanHQ API.



[![logo](https://dhanhq.co/docs/v2/img/DhanHQ_logo.svg)](https://dhanhq.co/ "Ver 2.0 / API Documentation")

Ver 2.0 / API Documentation



EDIS



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
- [Portfolio](https://dhanhq.co/docs/v2/portfolio/)
- [ ]
EDIS
[EDIS](https://dhanhq.co/docs/v2/edis/)
Table of contents


- [Generate T-PIN](https://dhanhq.co/docs/v2/edis/#generate-t-pin)
- [Generate eDIS Form](https://dhanhq.co/docs/v2/edis/#generate-edis-form)
- [EDIS Status & Inquiry](https://dhanhq.co/docs/v2/edis/#edis-status-inquiry)

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


- [Generate T-PIN](https://dhanhq.co/docs/v2/edis/#generate-t-pin)
- [Generate eDIS Form](https://dhanhq.co/docs/v2/edis/#generate-edis-form)
- [EDIS Status & Inquiry](https://dhanhq.co/docs/v2/edis/#edis-status-inquiry)

# EDIS

To sell holding stocks, one needs to complete the CDSL eDIS flow, generate T-PIN & mark stock to complete the sell action.

|     |     |     |
| --- | --- | --- |
| GET | /edis/tpin | Generate T-PIN |
| POST | /edis/from | Retrieve escaped html form & enter T-PIN |
| GET | /edis/inquire/{isin} | Inquire the status of stock for edis approval. |

## Generate T-PIN

Get T-Pin on your registered mobile number using this API.

```
    curl --request GET \
    --url https://api.dhan.co/v2/edis/tpin \
    --header 'Content-Type: application/json' \
    --header 'access-token: JWT'
```

Request Structure

_No Body_

**Response Structure**

```
202 Accepted
```

## Generate eDIS Form

Retrieve escaped html form of CDSL and enter T-PIN to mark the stock for EDIS approval. User has to render this form at their end to unescape.
You can get ISIN of portfolio stocks, in response of holdings API.

```
    curl --request POST \
    --url https://api.dhan.co/v2/edis/form \
    --header 'Content-Type: application/json' \
    --header 'access-token: ' \
    --data '{}'
```

**Request Structure**

```
    {
        "isin": "INE733E01010",
        "qty": 1,
        "exchange": "NSE",
        "segment": "EQ",
        "bulk": true
    }
```

**Parameters**

| Field | Field Type | Description |
| --- | --- | --- |
| isin | string | International Securities Identification Number |
| qty | int | Number of shares to mark for edis transaction |
| exchange | string | Exhange `NSE``BSE` |
| segment | string | Segment `EQ` |
| bulk | boolean | To mark edis for all stocks in portfolio |

**Response Structure**

```
{
    "dhanClientId": "1000000401",
    "edisFormHtml": "<!DOCTYPE html> <html>     <script>window.onload= function()
        {submit()};function submit(){ document.getElementById(\"submitbtn\").click();    }
        </script><body onload=\"submit()\">     <form name=\"frmDIS\" method=\"post\"
        action=\"https://edis.cdslindia.com/eDIS/VerifyDIS/\" style=\"
        text-align: center; margin-top: 35px; /* margin-bottom: 15px; */ \">
        <input type= \"hidden\" name= \"DPId\" value= \"83400\" >
        <input type= \"hidden\" name= \"ReqId\" value= \"291951000000401\" >
        <input type= \"hidden\" name= \"Version\" value= \"1.1\" >
        <input type= \"hidden\" name= \"TransDtls\" value= \"kQBOKYtPSbWmbLYOih9ZXaLZuA3Ig5ycFPangwWZKTPgmIqdfXL58qN3tGfDlVH+S613mfqTkIWVkQTiMrqUHkzvTRxkr7NtJtP7O3Z7+Xro9Fs5svt2tQDrNJGSd1oEqc4dhoc+FCS8u9ZhNCFqkZ30djjKqjTp1j12fv4cZVwzupyLfVVyh0U8TwwqSAEP4mdq3uiimxADlrHVRrn5NSL+ndUn5BhplI7F9Ksiscj9hxz6iK2Os8m5JMFBU7bmNmIWWHEgTLOz0N+roldjRs2M8mVXSx+M+41jrdSWaCnMxvm+L2HNbsT94Zv8wEWmxSCcSDcvVFhbpcWP5RVQMHQpV6cw6+s7qfn1AWexGiUJk3APPnhYdXPjwIewhyL5rEhNRnCy+cZaJSzsBpatfOJO3xjrZd6zDv6raf/4EUwHJ8yOVYjG5L4uAjnsfBy0SCuqYnxmMphI8/mnJlopH71Kvi9IkH/wPBiKvOkNYpJD3+CFXE6No3RrRiC8DF1pkSaMm7IxdHr0ui2QBmyqcg==\" >
        <input style=\"display: none;\" id=\"submitbtn\" type= \"submit\" value=\"Submit\">
        </form> </body> </html>"
}
```

**Parameters**

|     |     |     |
| --- | --- | --- |
| Field | Field type | Description |
| dhanClientId | string | User specific identification generated by Dhan |
| edisFormHtml | string | Escaped HTML Form |

## EDIS Status & Inquiry

You can check the status of stock whether it is approved and marked for sell action. User have to enter ISIN of the stock.
An International Securities Identification Number (ISIN) is a 12-digit alphanumeric code that uniquely identifies a specific security.
You can get ISIN of portfolio stocks, in response of holdings API.

Alternatively, you can pass "ALL" instead of ISIN to get eDIS status of all holdings in your portfolio.

cURLPython

```
curl --request GET \
--url https://api.dhan.co/v2/edis/inquire/{isin} \
--header 'Content-Type: application/json' \
--header 'access-token: JWT'
```

```
dhan.edis_inquiry(isin)
```

**Request Structure**

_No Body_

**Response Structure**

```
{
    "clientId": "1000000401",
    "isin": "INE00IN01015",
    "totalQty": 10,
    "aprvdQty": 4,
    "status": "SUCCESS",
    "remarks": "eDIS transaction done successfully"
}
```

**Parameters**

| Field | Field type | Description |
| --- | --- | --- |
| clientId | string | User specific identification |
| isin | string | International Securities Identification Number |
| totalQty | string | Total number of shares for given stock |
| aprvdQty | string | Number of approved stocks |
| status | string | Status of the edis order |
| remark | string | remarks of the order status |

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