<---qa tools----->: ## -----------------------------------------------------------------------
phpunit: start ## Run all tests
	$(DOCKER_COMPOSE) run --rm phpcli vendor/bin/phpunit --bootstrap ./tests/bootstrap.php /app/tests
.PHONY: phpunit

phpunit-reports: start ## Run all tests with reports
	$(DOCKER_COMPOSE) run --rm -e PCOV_ENABLED=1 phpcli vendor/bin/phpunit --bootstrap ./tests/bootstrap.php /app/tests --coverage-clover tests/reports/clover.xml --coverage-xml tests/reports/coverage-xml
.PHONY: phpunit-reports

phpunit-coverage: start ## Run all tests with coverage text
	$(DOCKER_COMPOSE) run --rm -e PCOV_ENABLED=1 phpcli vendor/bin/phpunit --bootstrap ./tests/bootstrap.php /app/tests --coverage-text
.PHONY: phpunit-coverage

phpunit-coverage-html: start ## Run all tests with HTML coverage
	$(DOCKER_COMPOSE) run --rm -e PCOV_ENABLED=1 phpcli vendor/bin/phpunit --bootstrap ./tests/bootstrap.php /app/tests --coverage-html tests/reports/coverage-html
.PHONY: phpunit-coverage-html

# No CLI path argument on purpose: a positional path SUBSTITUTES phpstan.neon's
# own `paths:` key. This repo's phpstan.neon declares `src` AND `tests`, so the
# former `analyse /app/src` silently dropped the whole test tree from the gate —
# the config said one thing, the gate did another. Measured before the change:
# `analyse /app/src` and `analyse -c phpstan.neon` (src + tests) both report 0
# findings at level 8, so widening the scope costs nothing here and now actually
# holds the tests to the contracts they implement.
phpstan: ## Run PHPStan analysis (paths come from phpstan.neon: src + tests)
	$(DOCKER_COMPOSE) run --rm --no-deps phpcli vendor/bin/phpstan analyse -c phpstan.neon
.PHONY: phpstan

phpcs: ## Run coding standards
	$(DOCKER_COMPOSE) run --rm --no-deps phpcli vendor/bin/phpcs /app/src
.PHONY: phpcs
