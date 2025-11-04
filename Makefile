.PHONY: up down

DC=docker-compose

docker-compose-down:
	@$(DC) down -v --remove-orphans --timeout 1

docker-compose-up:
	@$(DC) up --build -d

docker-compose-up-force-build:
	@$(DC) up --build -d

up:
	$(AT)set -e; \
	$(MAKE) docker-compose-down; \
	$(MAKE) docker-compose-up;

down:
	$(AT)set -e; \
	$(MAKE) docker-compose-down;
