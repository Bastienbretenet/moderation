.PHONY: build up bash test fixtures

build:
	docker compose build

up:
	docker compose up -d --wait

bash:
	docker compose exec php bash

test:
	docker compose exec php vendor/bin/phpunit

fixtures:
	docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
