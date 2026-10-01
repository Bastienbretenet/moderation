.PHONY: build up bash test

build:
	docker compose build

up:
	docker compose up -d --wait

bash:
	docker compose exec php bash

test:
	docker compose exec php vendor/bin/phpunit
