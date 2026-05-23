PLUGIN_SLUG = wpwing-wishlist-and-waitlist-for-woocommerce
DIST_DIR    = dist
BUILD_DIR   = $(DIST_DIR)/$(PLUGIN_SLUG)
ZIP_FILE    = $(DIST_DIR)/$(PLUGIN_SLUG).zip

PHP      = php
COMPOSER = composer
PHPCS    = ./vendor/bin/phpcs
PHPCBF   = ./vendor/bin/phpcbf

.PHONY: all install lint phpcs phpcbf check dist clean

## Install all dependencies (including dev)
install:
	$(COMPOSER) install

## PHP syntax check on all plugin files — fails only on actual syntax errors
lint:
	@errors=0; \
	for f in $$(find . -name "*.php" ! -path "./vendor/*" ! -path "./dist/*"); do \
		out=$$($(PHP) -l "$$f" 2>&1); \
		if ! echo "$$out" | grep -q "^No syntax errors"; then \
			echo "$$out"; errors=1; \
		fi; \
	done; \
	[ $$errors -eq 0 ] && echo "Lint: all PHP files OK." || exit 1

## Run PHP_CodeSniffer
phpcs:
	$(PHPCS) --standard=phpcs.xml.dist -p

## Auto-fix PHPCS violations where possible
phpcbf:
	$(PHPCBF) --standard=phpcs.xml.dist -p

## Run lint + phpcs
check: lint phpcs

## Build a shippable zip (runs check first, always starts clean)
dist: check
	@echo "--- Removing previous dist ---"
	rm -rf "$(DIST_DIR)"
	mkdir -p "$(BUILD_DIR)"
	@echo "--- Installing production dependencies ---"
	$(COMPOSER) install --no-dev --optimize-autoloader
	@echo "--- Copying plugin files ---"
	rsync -a --exclude-from=.distignore . "$(BUILD_DIR)/"
	@echo "--- Creating zip ---"
	cd "$(DIST_DIR)" && zip -r "$(PLUGIN_SLUG).zip" "$(PLUGIN_SLUG)/"
	rm -rf "$(BUILD_DIR)"
	@echo "--- Restoring dev dependencies ---"
	$(COMPOSER) install
	@echo ""
	@echo "Built: $(ZIP_FILE)"

## Remove the dist folder
clean:
	rm -rf "$(DIST_DIR)"
