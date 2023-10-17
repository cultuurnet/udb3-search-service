.PHONY: up down bash install ci stan cs cs-fix test migrate migrate-force test-filter

up:
	docker-compose up -d

down:
	docker-compose down

bash:
	docker exec -it search.uitdatabank bash

install:
	docker exec -it search.uitdatabank composer install

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

migrate:
	docker exec -it search.uitdatabank ./bin/app.php elasticsearch:migrate

migrate-force:
	docker exec -it search.uitdatabank ./bin/app.php elasticsearch:migrate --force