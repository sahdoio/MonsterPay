# Variables
DC=USERID=$(USERID) GROUPID=$(GROUPID) docker compose --file docker-compose.yml --env-file ./src/.env
DC_PROD=docker compose --file docker-compose.prod.yml

.PHONY: up down sh logs setup test migrate rollback horizon app-log prod-up prod-down prod-logs

USERID := $(shell id -u)
GROUPID := $(shell id -g)

show-vars:
	@echo "USERID: $(USERID)"
	@echo "GROUPID: $(GROUPID)"

go: stop
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

# ⚙️ Produção / Rinha de Backend
prod-up:
	$(DC_PROD) up -d --build

prod-down:
	$(DC_PROD) down

prod-logs:
	$(DC_PROD) logs -f --tail=20
