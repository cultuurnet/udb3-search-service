.PHONY: up down bash config install migrate migrate-force ci stan cs cs-fix test

up:
	docker-compose up -d

down:
	docker-compose down

build:
	docker-compose up --build

bash:
	docker-compose exec -it search bash

config:
	sh ./docker/config.sh

install:
	docker-compose exec -it search composer install

migrate:
	docker-compose exec -it search ./bin/app.php elasticsearch:migrate

migrate-force:
	docker-compose exec -it search ./bin/app.php elasticsearch:migrate --force

ci:
	docker-compose exec -it search composer ci

stan:
	docker-compose exec -it search composer phpstan

cs:
	docker-compose exec -it search composer cs

cs-fix:
	docker-compose exec -it search composer cs-fix

test:
	docker-compose exec -it search composer test

test-filter:
	docker-compose exec -it search composer test -- --filter=$(filter)