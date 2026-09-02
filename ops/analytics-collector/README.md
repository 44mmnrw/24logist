# First-party analytics collector

Small standalone PHP 8.2+ endpoint for forwarding an explicitly consented,
allow-listed subset of browser events to GA4 Measurement Protocol.

Production layout:

```text
/var/www/logist_sys/data/www/a.24logist.ru/index.php
/var/www/logist_sys/data/.analytics.env
```

The environment file must be readable only by the site user and must never be
placed in the public web root. Keep `ANALYTICS_ENABLED=false` until the GA4
measurement ID and Measurement Protocol API secret have been configured.

Public `GET /` requests return an empty `404`; collector configuration is not
exposed over HTTP. `POST /` accepts payloads in this form:

```json
{
  "consent": true,
  "client_id": "123456789.1234567890",
  "session_id": 1234567890,
  "events": [
    {
      "name": "page_view",
      "params": {
        "page_location": "https://24logist.ru/example?ignored=yes",
        "page_title": "Example"
      }
    }
  ]
}
```

For cross-origin browser delivery, send the serialized JSON body with
`Content-Type: text/plain;charset=UTF-8`. This keeps the request CORS-simple on
hosting configurations that reject `OPTIONS`; the collector still parses and
validates the body strictly as JSON. Server-side clients may use
`application/json`.

Query strings and fragments are stripped from URL parameters before forwarding.
The endpoint does not store event bodies and forces advertising consent to
`DENIED`.
