# Security, Validation, Rate Limiting

## Security
- Secrets only via env; rotate; audit logs; TLS 1.2+; at-rest encryption for sensitive fields.
- RBAC for CLI/API; two-factor approvals in prod for privileged ops.
- CSRF: not applicable to CLI; protect HTTP endpoints with tokens; CORS locked.

## Validation
- Input schemas with `symfony/validator`; sanitize logs; block secrets.
- Tick rounding: nearest 0.05 increments, two decimals; reject out-of-band ticks.

## Rate Limiting
- Token-bucket per broker and API category; Redis keys `rate:{broker}:{category}`; headers `X-RateLimit-*`.

## Vulnerabilities
- SQL injection: prepared statements via `illuminate/database`.
- XSS: CLI only; for API responses ensure JSON encode and content-type.
- CSRF: require auth headers; no cookies; optional HMAC.

