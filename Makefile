.PHONY: up down bash config install migrate migrate-force ci stan cs cs-fix test

up:
	docker-compose up -d

down:
	docker-compose down

build:
	docker-compose up --build

bash:
	docker-compose exec search bash

config:
	sh ./docker/config.sh

install:
	docker-compose exec -T search composer install

migrate:
	docker-compose exec -T search ./bin/app.php elasticsearch:migrate

migrate-force:
	docker exec -it search.uitdatabank ./bin/app.php elasticsearch:migrate --force

ci:
	docker exec -it search.uitdatabank composer ci

stan:
	docker exec -it search.uitdatabank composer phpstan

cs:
	docker exec -it search.uitdatabank composer cs

cs-fix:
	docker exec -it search.uitdatabank composer cs-fix

test:
	docker exec -it search.uitdatabank composer test

test-filter:
	docker exec -it search.uitdatabank composer test -- --filter=$(filter)
