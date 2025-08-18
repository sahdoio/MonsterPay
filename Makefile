# Variables
DC=USERID=$(USERID) GROUPID=$(GROUPID) docker compose --file docker-compose.yml --env-file ./src/.env
DC_INFRA=docker compose --file ./infra/payment-processor/docker-compose.yml
DC_PROD=docker compose --file docker-compose.prod.yml

.PHONY: all go stop sh test test-report logs log infra-up infra-down prod-up prod-down prod-logs show-vars

USERID := $(shell id -u)
GROUPID := $(shell id -g)

show-vars:
	@echo "USERID: $(USERID)"
	@echo "GROUPID: $(GROUPID)"

go: infra-down infra-up stop
	$(DC) run monsterpay-api composer install
	$(DC) up -d --build
	make logs

stop:
	$(DC) down

sh:
	$(DC) exec monsterpay-api sh

test:
	$(DC) exec monsterpay-api composer test

test-report:
	$(DC) exec monsterpay-api vendor/bin/pest --coverage-html=report

logs:
	$(DC) logs -f --tail=10

log:
	$(DC) exec monsterpay-api tail -f runtime/logs/hyperf.log

infra-up:
	$(DC_INFRA) up -d --build

infra-down:
	$(DC_INFRA) down

prod-up:
	$(DC_PROD) up -d --build

prod-down:
	$(DC_PROD) down

prod-logs:
	$(DC_PROD) logs -f --tail=20

k6-test:
	k6 run infra/test/rinha.js
