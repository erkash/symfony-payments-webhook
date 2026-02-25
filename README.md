# Payments Webhook Processor (Symfony 6.4)

DDD-lite Symfony 6.4 service for processing payment webhooks with JWT auth, Doctrine ORM, Messenger + RabbitMQ, and Redis cache.

## Stack

- PHP 8.2+
- Symfony 6.4
- MySQL 8 (Doctrine ORM + Migrations)
- Redis (cache)
- RabbitMQ (Messenger)
- LexikJWTAuthenticationBundle
- PHPUnit

## Quickstart (via Makefile)

1. View available commands:

```bash
make help
```

2. Prepare env:

```bash
cp .env.example .env
```

Set `WEBHOOK_SECRET_STRIPE` (or the secret for the provider you use) for webhook signature generation and validation.

3. Start infrastructure:

```bash
make up
```

4. Install dependencies:

```bash
make composer-install
```

5. Generate JWT keypair:

```bash
make jwt
```

JWT keys are generated locally. Private keys must never be committed.

6. Apply migrations:

```bash
make migrate
```

7. Run consumer (recommended and required for processing webhook events and updating payment statuses):

```bash
make consume
```

8. Check health endpoint:

```bash
make smoke
```

API is available at `http://localhost:8080`.

Default API user (dev only):
- username: `api`
- password: `secret`

For production, you must replace the user/password and all secrets.

## Endpoints

- `POST /api/auth/login` - JWT issuance
- `POST /api/payments` - create payment
- `GET /api/payments/{id}` - fetch payment by id
- `POST /api/webhooks/{provider}` - receive webhook
- `GET /health` - health check

## Idempotency for Create Payment

`POST /api/payments` supports `Idempotency-Key` for production-like replay safety.

- First request with a new key creates payment: `201 Created`
- Repeated request with the same key returns existing payment: `200 OK`
- Response header `Idempotency-Replayed` is `false` for first call and `true` for replay
- Guarantee is enforced by MySQL unique constraint `(operation, idempotency_key)`, so it is race-safe under concurrent requests

```bash
IDEMPOTENCY_KEY="pay-create-order-123"

curl -i -s -X POST http://localhost:8080/api/payments \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -H "Idempotency-Key: $IDEMPOTENCY_KEY" \
  -d '{"amount":2500,"currency":"USD"}'
```

## Makefile commands

- `make up` - start services (`app`, `nginx`, `mysql`, `redis`, `rabbitmq`)
- `make down` - stop services
- `make restart` - restart services
- `make ps` - container status
- `make logs` - `app` logs
- `make sh` - shell in `app`
- `make migrate` - apply migrations
- `make migrations-status` - migration status
- `make schema-validate` - validate Doctrine schema
- `make cache-clear` - clear Symfony cache
- `make consume` - run messenger consumer
- `make test` - run PHPUnit
- `make db` - MySQL shell
- `make reset-db` - drop/create/migrate DB
- `make redis-cli` - Redis CLI
- `make rabbitmq-ui` - RabbitMQ Management UI URL

## Curl Examples

### Login

```bash
curl -s -X POST http://localhost:8080/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"username":"api","password":"secret"}'
```

### Extract JWT with jq

```bash
TOKEN=$(curl -s -X POST http://localhost:8080/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"username":"api","password":"secret"}' | jq -r .token)

echo "$TOKEN"
```

### Create Payment

```bash
curl -s -X POST http://localhost:8080/api/payments \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"amount":2500,"currency":"USD"}'
```

### Get Payment

```bash
curl -s -X GET http://localhost:8080/api/payments/<id> \
  -H "Authorization: Bearer $TOKEN"
```

### Webhook (HMAC signature)

```bash
PAYLOAD='{"paymentId":"<payment-uuid>","status":"succeeded"}'
SIGNATURE=$(printf "%s" "$PAYLOAD" | openssl dgst -sha256 -hmac "$WEBHOOK_SECRET_STRIPE" | awk '{print $2}')

curl -s -X POST http://localhost:8080/api/webhooks/stripe \
  -H "X-Signature: $SIGNATURE" \
  -H 'Content-Type: application/json' \
  -d "$PAYLOAD"
```

### Health

```bash
curl -s http://localhost:8080/health
```

## Structure (DDD-lite)

```text
src/Payments/Domain
src/Payments/Application
src/Payments/Infrastructure
```

## Notes

- `WebhookEvent` is always stored; if the signature is invalid, the consumer marks the event as processed without updating the payment.
- The consumer expects `paymentId` in payload as a UUID of an existing `Payment`.
