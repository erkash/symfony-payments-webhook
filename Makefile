SHELL := /bin/bash

DC := docker compose
APP := app

.PHONY: help up down restart ps logs sh composer-install composer-require jwt migrate migrations-status schema-validate cache-clear consume test db reset-db rabbitmq-ui redis-cli smoke

help: ## Show available commands
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z0-9_-]+:.*##/ {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

up: ## Start containers
	$(DC) up -d --build

down: ## Stop containers
	$(DC) down

restart: ## Restart containers
	$(MAKE) down
	$(MAKE) up

ps: ## Show container status
	$(DC) ps

logs: ## Tail app logs
	$(DC) logs -f $(APP)

sh: ## Open shell in app container
	$(DC) exec $(APP) sh

composer-install: ## Install Composer dependencies
	$(DC) exec $(APP) composer install

composer-require: ## Require a Composer package (usage: make composer-require pkg=vendor/package)
	$(DC) exec $(APP) composer require $(pkg)

jwt: ## Generate JWT keypair
	$(DC) exec $(APP) php bin/console lexik:jwt:generate-keypair

migrate: ## Run database migrations
	$(DC) exec $(APP) php bin/console doctrine:migrations:migrate --no-interaction

migrations-status: ## Show migration status
	$(DC) exec $(APP) php bin/console doctrine:migrations:status

schema-validate: ## Validate Doctrine schema
	$(DC) exec $(APP) php bin/console doctrine:schema:validate

cache-clear: ## Clear Symfony cache
	$(DC) exec $(APP) php bin/console cache:clear

consume: ## Start async messenger consumer
	$(DC) exec $(APP) php bin/console messenger:consume async -vv

test: ## Run PHPUnit tests
	$(DC) exec $(APP) vendor/bin/phpunit

db: ## Open MySQL shell
	$(DC) exec mysql mysql -uapp -papp app

reset-db: ## Drop, create, and migrate database
	$(DC) exec $(APP) php bin/console doctrine:database:drop --force --if-exists
	$(DC) exec $(APP) php bin/console doctrine:database:create --if-not-exists
	$(DC) exec $(APP) php bin/console doctrine:migrations:migrate --no-interaction

rabbitmq-ui: ## Print RabbitMQ management UI URL
	@echo "RabbitMQ UI: http://localhost:15672 (guest/guest)"

redis-cli: ## Open redis-cli
	$(DC) exec redis redis-cli

smoke: ## Run health check
	@curl -fsS http://localhost:8080/health && echo
