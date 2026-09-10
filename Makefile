.PHONY: up down pull build bash logs config install migrate migrate-force ci stan cs cs-fix test test-filter destroy \
	acc-test-build acc-test-up acc-test-migrate acc-test-logs acc-test-down

# The base file on its own, without docker-compose.override.yml
ACC_TEST_COMPOSE = docker compose -f docker-compose.yml

# The image the acceptance-test stack runs. The default is a local tag produced by
# `acc-test-build` from this checkout, so the branch's own application code is what gets tested. 
SEARCH_IMAGE ?= uitdatabank/search-api:acc-test
export SEARCH_IMAGE

up:
	docker compose up -d --wait --wait-timeout 300

down:
	docker compose down

# Rebuilds the dev image and recreates the containers on it. Only needed after a
# change to the Dockerfile: application code and vendor/ are bind-mounted, so
# nothing else requires a rebuild. Add --pull to also pick up a newer php:8.1-fpm.
build:
	docker compose up --build --detach

# Only Elasticsearch and nginx: the app image is built here, not pulled
pull:
	docker compose pull --ignore-buildable

bash:
	docker compose exec -it search bash

logs:
	docker compose logs -f

config:
	sh ./docker/config.sh

install:
	docker compose exec -it search composer install

migrate:
	docker compose exec -it search ./bin/app.php elasticsearch:migrate

migrate-force:
	docker compose exec -it search ./bin/app.php elasticsearch:migrate --force

ci:
	docker compose exec -it search composer ci

stan:
	docker compose exec -it search composer phpstan

cs:
	docker compose exec -it search composer cs

cs-fix:
	docker compose exec -it search composer cs-fix

test:
	docker compose exec -it search composer test

test-filter:
	docker compose exec -it search composer test -- --filter=$(filter)

destroy:
	docker compose down --rmi all --volumes

# Builds the production target — no dev tooling, vendor/ baked in, no source mount from this checkout, so the tests exercise this branch's application code.
acc-test-build:
	docker build -t $(SEARCH_IMAGE) .

acc-test-up:
	$(ACC_TEST_COMPOSE) up -d --wait --wait-timeout 300

acc-test-migrate:
	$(ACC_TEST_COMPOSE) exec -T search ./bin/app.php elasticsearch:migrate

acc-test-logs:
	$(ACC_TEST_COMPOSE) logs --no-color --timestamps

acc-test-down:
	$(ACC_TEST_COMPOSE) down -v
