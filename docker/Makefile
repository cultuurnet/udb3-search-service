.PHONY: ci stan cs cs-fix test

install:
	docker exec -it php.search composer install

ci:
	docker exec -it php.search composer ci

stan:
	docker exec -it php.search composer phpstan

cs:
	docker exec -it php.search composer cs

cs-fix:
	docker exec -it php.search composer cs-fix

test:
	docker exec -it php.search composer test

migrate:
	docker exec -it php.search ./vendor/bin/doctrine-dbal migrations:migrate --no-interaction
