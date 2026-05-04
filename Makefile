.DEFAULT_GOAL := help

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

install: ## First-time setup: copy config, install hooks and start containers
	@test -f config.php || cp config.dist.php config.php
	@git config core.hooksPath githooks
	@$(MAKE) up

up: ## Start containers
	docker compose up -d

down: ## Stop containers
	docker compose down

build: ## Rebuild Docker images
	docker compose build --no-cache

logs: ## Show container logs
	docker compose logs -f

shell: ## Open a shell in the PHP container
	docker compose exec app bash

db: ## Open a MySQL shell
	docker compose exec database mysql -u adhesion -padhesion adhesion

test-db-init: ## Create/reset the isolated test database (adhesion_test)
	docker compose exec -T database mysql -uroot -proot -e "DROP DATABASE IF EXISTS adhesion_test; CREATE DATABASE adhesion_test DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON adhesion_test.* TO 'adhesion'@'%'; FLUSH PRIVILEGES;"

test: ## Run PHPUnit tests in Docker (isolated DB: adhesion_test)
	docker compose exec app bash -c "cd /var/www/html && composer install --quiet && vendor/bin/phpunit"

fixtures: ## Load test fixtures into the database
	docker compose exec -T database mysql -u adhesion -padhesion adhesion < docker/fixtures.sql

reset-db: ## Destroy and recreate the database volume
	docker compose down -v
	@$(MAKE) up

.PHONY: help install up down build logs shell db test test-db-init fixtures reset-db
