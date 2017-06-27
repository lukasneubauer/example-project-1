.DEFAULT_GOAL := help

.PHONY: help
help:
	@echo 'Usage:'
	@echo '  make [target]'
	@echo ''
	@echo 'Targets:'
	@echo '  init                     Initialize application, e.g.: re-create database and load database migrations.'
	@echo '  init-full                Initialize application, e.g.: re-create database, load database migrations and load fixtures.'
	@echo '  init-full-for-phpunit    Initialize application, e.g.: re-create database, load database migrations and load fixtures for phpunit.'
	@echo '  init-full-for-dredd      Initialize application, e.g.: re-create database, load database migrations and load fixtures for dredd.'
	@echo '  db-drop                  Drop database.'
	@echo '  db-migrations            Load database migrations.'
	@echo '  db-fixtures              Load database fixtures.'
	@echo '  db-fixtures-for-phpunit  Load database fixtures for phpunit.'
	@echo '  db-fixtures-for-dredd    Load database fixtures for dredd.'
	@echo '  generate-api-client-id   Generate value for api client id.'
	@echo '  generate-api-key         Generate value for api key.'
	@echo '  generate-api-token       Generate value for api token.'
	@echo '  generate-security-code   Generate value for security code.'
	@echo '  generate-token           Generate value for token.'
	@echo '  generate-uuid            Generate value for uuid.'

.PHONY: init
init: db-drop db-migrations

.PHONY: init-full
init-full: db-fixtures

.PHONY: init-full-for-phpunit
init-full-for-phpunit: db-fixtures-for-phpunit

.PHONY: init-full-for-dredd
init-full-for-dredd: db-fixtures-for-dredd

.PHONY: db-drop
db-drop:
	php scripts/console doctrine:schema:drop --full-database --force

.PHONY: db-migrations
db-migrations:
	php scripts/console doctrine:migrations:migrate --no-interaction

.PHONY: db-fixtures
db-fixtures: db-drop db-migrations
	php scripts/load_fixtures

.PHONY: db-fixtures-for-phpunit
db-fixtures-for-phpunit: db-drop db-migrations
	php scripts/load_fixtures_for_phpunit

.PHONY: db-fixtures-for-dredd
db-fixtures-for-dredd: db-drop db-migrations
	php scripts/load_fixtures_for_dredd

.PHONY: generate-api-client-id
generate-api-client-id:
	@php scripts/generate_api_client_id

.PHONY: generate-api-key
generate-api-key:
	@php scripts/generate_api_key

.PHONY: generate-api-token
generate-api-token:
	@php scripts/generate_api_token

.PHONY: generate-security-code
generate-security-code:
	@php scripts/generate_security_code

.PHONY: generate-token
generate-token:
	@php scripts/generate_token

.PHONY: generate-uuid
generate-uuid:
	@php scripts/generate_uuid
