PLUGIN_SLUG = wpwing-smart-wishlist-product-waitlist-for-woocommerce
SRC_DIR     = src
DIST_DIR    = dist
BUILD_DIR   = $(DIST_DIR)/$(PLUGIN_SLUG)
ZIP_FILE    = $(DIST_DIR)/$(PLUGIN_SLUG).zip

PHP      = php
COMPOSER = composer
PHPCS    = ./vendor/bin/phpcs
PHPCBF   = ./vendor/bin/phpcbf

.DEFAULT_GOAL := help

help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'
.PHONY: help

setup: ## Bootstrap dev environment (first-time setup)
	$(COMPOSER) install --no-interaction
	$(COMPOSER) install --no-interaction --working-dir=$(SRC_DIR)
	@printf '#!/bin/sh\nmake phpcs\n' > .git/hooks/pre-push
	@chmod +x .git/hooks/pre-push
	@echo "Dev environment ready."
.PHONY: setup

phpcs: ## Run PHP_CodeSniffer and report errors
	$(PHPCS) --standard=phpcs.xml.dist -p
.PHONY: phpcs

phpcbf: ## Auto-fix PHP code with PHP Code Beautifier and Fixer
	$(PHPCBF) --standard=phpcs.xml.dist -p
.PHONY: phpcbf

analyse: ## Run PHPStan static analysis
	./vendor/bin/phpstan analyse
.PHONY: analyse

lint: ## PHP syntax check on all plugin files
	@errors=0; \
	for f in $$(find $(SRC_DIR) -name "*.php" ! -path "$(SRC_DIR)/vendor/*"); do \
		out=$$($(PHP) -l "$$f" 2>&1); \
		if ! echo "$$out" | grep -q "^No syntax errors"; then \
			echo "$$out"; errors=1; \
		fi; \
	done; \
	[ $$errors -eq 0 ] && echo "Lint: all PHP files OK." || exit 1
.PHONY: lint

version-check: ## Verify version strings are consistent across all files
	@V=$$(grep 'Version:' $(SRC_DIR)/$(PLUGIN_SLUG).php | grep -o '[0-9]\+\.[0-9]\+\.[0-9]\+' | head -1); \
	echo "Checking version $$V ..."; \
	errors=0; \
	grep -q "WPWING_WL_VERSION', '$$V'" $(SRC_DIR)/$(PLUGIN_SLUG).php || { echo "  FAIL: WPWING_WL_VERSION constant"; errors=1; }; \
	grep -qP "Stable tag:\s+$$V" $(SRC_DIR)/readme.txt                 || { echo "  FAIL: Stable tag in readme.txt"; errors=1; }; \
	[ $$errors -eq 0 ] && echo "  All version strings match $$V ✓" || exit 1
.PHONY: version-check

check: lint phpcs version-check ## Run lint, phpcs, and version consistency check
.PHONY: check

version: ## Bump version strings — usage: make version V=1.1.0
	@[ -n "$(V)" ] || (echo "Usage: make version V=1.1.0" && exit 1)
	sed -i "s/Version: .*/Version: $(V)/" $(SRC_DIR)/$(PLUGIN_SLUG).php
	sed -i "s/WPWING_WL_VERSION', '.*'/WPWING_WL_VERSION', '$(V)'/" $(SRC_DIR)/$(PLUGIN_SLUG).php
	sed -i "s/Stable tag: .*/Stable tag: $(V)/" $(SRC_DIR)/readme.txt
	@echo "Version bumped to $(V)"
.PHONY: version

pot: ## Regenerate the .pot translation file
	wp i18n make-pot $(SRC_DIR) $(SRC_DIR)/languages/$(PLUGIN_SLUG).pot \
		--exclude=vendor,node_modules
.PHONY: pot

zip: ## Build distributable zip into dist/ (runs check first)
	$(MAKE) check
	rm -rf "$(BUILD_DIR)"
	mkdir -p "$(BUILD_DIR)"
	rsync -r --exclude-from=.distignore $(SRC_DIR)/ $(BUILD_DIR)/
	$(COMPOSER) install --no-dev --optimize-autoloader --working-dir=$(BUILD_DIR)
	rm -f $(BUILD_DIR)/composer.json $(BUILD_DIR)/composer.lock
	cd "$(DIST_DIR)" && zip -r "$(PLUGIN_SLUG).zip" "$(PLUGIN_SLUG)/"
	rm -rf "$(BUILD_DIR)"
	unzip -t $(ZIP_FILE) > /dev/null
	@echo "Built: $(ZIP_FILE)"
.PHONY: zip

release: ## Full release — usage: make release V=1.1.0
	@[ -n "$(V)" ] || (echo "Usage: make release V=1.1.0" && exit 1)
	$(MAKE) version V=$(V)
	$(MAKE) zip
	@echo ""
	@echo "Next: git commit -am 'Release v$(V)' && make tag V=$(V) && git push && git push --tags"
.PHONY: release

tag: ## Create annotated git tag — usage: make tag V=1.1.0
	@[ -n "$(V)" ] || (echo "Usage: make tag V=1.1.0" && exit 1)
	git tag -a "v$(V)" -m "Release v$(V)"
	@echo "Tagged v$(V) — push with: git push --tags"
.PHONY: tag

changelog: ## Print commits since last tag
	@LAST=$$(git describe --tags --abbrev=0 2>/dev/null); \
	if [ -n "$$LAST" ]; then \
		echo "Commits since $$LAST:"; \
		git log $$LAST..HEAD --pretty=format:"- %s" --no-merges; \
	else \
		echo "No tags yet — showing all commits:"; \
		git log --pretty=format:"- %s" --no-merges; \
	fi
.PHONY: changelog

clean: ## Remove the dist directory
	rm -rf "$(DIST_DIR)"
.PHONY: clean

dev: ## Start local WordPress dev environment
	docker compose --profile dev up -d --wait db wordpress
	docker compose --profile dev run --rm wpcli
	docker compose --profile dev up -d caddy
	@echo ""
	@echo "Site:  https://smart-wishlist.local"
	@echo "Admin: https://smart-wishlist.local/wp-admin  (admin / password)"
	@echo ""
	@echo "First time? Run: make caddy-trust"
.PHONY: dev

caddy-trust: ## Trust Caddy's local CA (run once per machine, requires sudo)
	@echo "Waiting for Caddy to generate its CA..."
	@sleep 3
	docker compose --profile dev cp caddy:/data/caddy/pki/authorities/local/root.crt /tmp/caddy-root.crt
	sudo cp /tmp/caddy-root.crt /usr/local/share/ca-certificates/caddy-local.crt
	sudo update-ca-certificates
	@echo "Done. Restart your browser."
.PHONY: caddy-trust

dev-stop: ## Stop the dev environment
	docker compose --profile dev down
.PHONY: dev-stop

test-unit: ## Run PHPUnit tests (no WordPress stack needed)
	docker compose --profile unit run --rm unit
.PHONY: test-unit

test-e2e: ## Run Playwright E2E tests (spins up full stack)
	docker compose --profile e2e up -d --wait db wordpress
	docker compose --profile e2e run --rm wpcli
	docker compose --profile e2e up -d caddy
	docker compose --profile e2e run --rm e2e
.PHONY: test-e2e

env-reset: ## Wipe all Docker volumes and containers
	docker compose --profile dev --profile unit --profile e2e down -v
.PHONY: env-reset
