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
# 1. Клонируй репозиторий
git clone <repo-url>
cd notification-service

# 2. Скопируй .env
cp .env.example .env

# 3. Подними все контейнеры
docker compose up --build -d

# 4. Сгенерируй ключ
docker compose exec app php artisan key:generate

# 5. Запусти миграции
docker compose exec app php artisan migrate

# 6. Заполни тестовыми данными
docker compose exec app php artisan db:seed
```

## Сервисы после запуска

| Сервис | URL |
|---|---|
| API | http://localhost:8080/api/v1 |
| Swagger | http://localhost:8080/api/documentation |
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
