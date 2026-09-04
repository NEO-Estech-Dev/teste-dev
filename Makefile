DC := docker compose
EXEC := $(DC) exec app

.DEFAULT_GOAL := help
.PHONY: help build up down restart logs shell migrate fresh seed ingest test test-mysql pint

help: ## List the available targets
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

build: ## Build the application image
	$(DC) build

up: ## Start the stack (installs deps, waits for MySQL, migrates, serves on :8000)
	@[ -f .env ] || cp .env.example .env
	$(DC) up -d --build

down: ## Stop the stack
	$(DC) down

restart: ## Restart the application container
	$(DC) restart app

logs: ## Follow the application logs
	$(DC) logs -f app

shell: ## Open a shell in the application container
	$(EXEC) bash

migrate: ## Run pending migrations
	$(EXEC) php artisan migrate

fresh: ## Drop everything and re-run migrations with seeds
	$(EXEC) php artisan migrate:fresh --seed

seed: ## Run the database seeders
	$(EXEC) php artisan db:seed

ingest: ## Ingest every Pokemon from the PokeAPI
	$(EXEC) php artisan pokemon:ingest

test: ## Run the test suite (SQLite in memory)
	$(EXEC) env APP_ENV=testing CACHE_STORE=array DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= MAIL_MAILER=array QUEUE_CONNECTION=sync SESSION_DRIVER=array php artisan test

test-mysql: ## Run the test suite against the MySQL container
	$(EXEC) env APP_ENV=testing CACHE_STORE=array DB_CONNECTION=mysql DB_HOST=mysql DB_DATABASE=pokemon_test MAIL_MAILER=array QUEUE_CONNECTION=sync SESSION_DRIVER=array php artisan test

pint: ## Format the codebase
	$(EXEC) vendor/bin/pint
