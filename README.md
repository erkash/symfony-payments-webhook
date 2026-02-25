# Payments Webhook Processor (Symfony 6.4)

DDD-lite Symfony 6.4 сервис для обработки платежных вебхуков с JWT-auth, Doctrine ORM, Messenger + RabbitMQ и Redis cache.

## Stack

- PHP 8.2+
- Symfony 6.4
- MySQL 8 (Doctrine ORM + Migrations)
- Redis (cache)
- RabbitMQ (Messenger)
- LexikJWTAuthenticationBundle
- PHPUnit

## Quickstart (через Makefile)

1. Посмотреть доступные команды:

```bash
make help
```

2. Подготовить env:

```bash
cp .env.example .env
```

Установи `WEBHOOK_SECRET_STRIPE` (или секрет нужного провайдера) для генерации и валидации подписи вебхуков.

3. Поднять инфраструктуру:

```bash
make up
```

4. Установить зависимости:

```bash
make composer-install
```

5. Сгенерировать JWT keypair:

```bash
make jwt
```

JWT-ключи генерируются локально. Приватные ключи нельзя коммитить.

6. Применить миграции:

```bash
make migrate
```

7. Запустить consumer (рекомендуется и нужен для обработки webhook-событий и обновления статусов платежей):

```bash
make consume
```

8. Проверить health endpoint:

```bash
make smoke
```

API доступен по адресу `http://localhost:8080`.

Default API user (dev only):
- username: `api`
- password: `secret`

Для production обязательно заменить пользователя/пароль и все секреты.

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

## Makefile команды

- `make up` - поднять сервисы (`app`, `nginx`, `mysql`, `redis`, `rabbitmq`)
- `make down` - остановить сервисы
- `make restart` - перезапуск
- `make ps` - статус контейнеров
- `make logs` - логи `app`
- `make sh` - shell в `app`
- `make migrate` - применить миграции
- `make migrations-status` - статус миграций
- `make schema-validate` - валидация схемы Doctrine
- `make cache-clear` - очистка cache Symfony
- `make consume` - запуск messenger consumer
- `make test` - запуск PHPUnit
- `make db` - MySQL shell
- `make reset-db` - drop/create/migrate БД
- `make redis-cli` - Redis CLI
- `make rabbitmq-ui` - URL RabbitMQ Management UI

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

- `WebhookEvent` сохраняется всегда; при невалидной подписи consumer помечает событие обработанным без обновления платежа.
- Consumer ожидает `paymentId` в payload как UUID существующего `Payment`.
