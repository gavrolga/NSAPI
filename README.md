# Notification Service

Микросервис массовых уведомлений на Laravel 11.

## Стек

- **PHP 8.4** + **Laravel 11**
- **PostgreSQL 16** — основная БД
- **Redis** — очереди + дедупликация
- **RabbitMQ** — брокер сообщений
- **Docker + docker-compose**

## Запуск

```bash
# 1. Клонировать репозиторий
git clone https://github.com/gavrolga/NSAPI
cd notification-service

# 2. Скопировать .env
cp .env.example .env

# 3. Поднять все контейнеры
docker compose up --build -d

# 4. Сгенерировать ключ
docker compose exec app php artisan key:generate

# 5. Запустить миграции
docker compose exec app php artisan migrate

# 6. Заполнить тестовыми данными
docker compose exec app php artisan db:seed
```

## Сервисы после запуска

| Сервис      | URL |
|-------------|---|
| API         | http://localhost:8080/api/v1 |
| Swagger  UI | http://localhost:8080/api/documentation |
| OpenAPI spec | [docs/openapi.json](docs/openapi.json) |
| RabbitMQ UI | http://localhost:15672 (ns_rabbit / secret) |

## API

### POST /api/v1/notifications
Запуск массовой рассылки.

```json
{
  "channel": "email",
  "message": "Ваш код доступа: 1234",
  "priority": "high",
  "idempotency_key": "unique-key-001",
  "recipient_ids": [1, 2, 3]
}
```

### GET /api/v1/subscribers/{id}/notifications
История уведомлений подписчика.

## Тесты

```bash
docker compose exec app php artisan test
```

## Архитектурные решения

- **Дедупликация** — через `idempotency_key` в Redis (TTL 24 часа)
- **Приоритеты** — две очереди `high` и `low`
- **Retry** — 3 попытки с паузами 10/60/300 секунд
- **Провайдеры** — заглушки `EmailGatewayStub` и `SmsGatewayStub`
