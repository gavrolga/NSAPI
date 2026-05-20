# Shortcuts for common development commands.
# Usage: make <target>

.PHONY: help up down build restart logs shell migrate seed test fresh

# Default target
help:
	@echo ""
	@echo " Notification Service — available commands:"
	@echo ""
	@echo " make up          — Start all containers (detached)"
	@echo " make down        — Stop and remove containers"
	@echo " make build       — Rebuild images"
	@echo " make restart     — Restart all containers"
	@echo " make logs        — Follow logs (all services)"
	@echo " make shell       — Open bash in app container"
	@echo " make migrate     — Run database migrations"
	@echo " make seed        — Seed database with test data"
	@echo " make fresh       — Fresh migrate + seed"
	@echo " make test        — Run PHPUnit tests"
	@echo " make test-cover  — Run tests with coverage report"
	@echo " make key         — Generate APP_KEY"
	@echo " make queue       — Show queue worker logs"
	@echo " make horizon     — Show horizon logs"
	@echo ""

# Docker
up:
	docker-compose up -d

down:
	docker-compose down

build:
	docker-compose build --no-cache

restart:
	docker-compose restart

logs:
	docker-compose logs -f

# App shortcuts
shell:
	docker-compose exec app bash

key:
	docker-compose exec app php artisan key:generate

migrate:
	docker-compose exec app php artisan migrate --force

seed:
	docker-compose exec app php artisan db:seed

fresh:
	docker-compose exec app php artisan migrate:fresh --seed

# Testing
test:
	docker-compose exec app php artisan test --parallel

test-cover:
	docker-compose exec app php artisan test --coverage

# Workers
queue:
	docker-compose logs -f worker

horizon:
	docker-compose logs -f horizon

# Full startup (first run)
install:
	cp -n .env.example .env || true
	docker-compose up -d --build
	sleep 5
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan migrate --force
	docker-compose exec app php artisan db:seed
	@echo ""
	@echo " Notification Service is ready!"
	@echo " API:        http://localhost:8080/api/v1"
	@echo " Swagger:    http://localhost:8080/api/documentation"
	@echo " RabbitMQ:   http://localhost:15672  (ns_rabbit / secret)"
	@echo " Horizon:    http://localhost:8080/horizon"
	@echo ""
