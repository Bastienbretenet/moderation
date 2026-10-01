.PHONY: build up bash test phpstan fixtures

build:
	docker compose build

up:
	docker compose up -d --wait

bash:
	docker compose exec php bash

test: phpstan
	docker compose exec php vendor/bin/phpunit

phpstan:
	docker compose exec php vendor/bin/phpstan analyse --memory-limit=512M

fixtures:
	docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
