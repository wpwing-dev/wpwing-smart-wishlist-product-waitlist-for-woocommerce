PLUGIN_SLUG = wpwing-wishlist-and-waitlist-for-woocommerce
DIST_DIR    = dist
BUILD_DIR   = $(DIST_DIR)/$(PLUGIN_SLUG)
ZIP_FILE    = $(DIST_DIR)/$(PLUGIN_SLUG).zip

PHP      = php
COMPOSER = composer
PHPCS    = ./vendor/bin/phpcs
PHPCBF   = ./vendor/bin/phpcbf

.PHONY: all install lint phpcs phpcbf check dist clean make-pot

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

## Regenerate the .pot translation template from source
make-pot:
	@mkdir -p languages
	xgettext \
		--language=PHP \
		--from-code=UTF-8 \
		--keyword=__ \
		--keyword=_e \
		--keyword=_n:1,2 \
		--keyword=_x:1,2c \
		--keyword=_nx:1,2,4c \
		--keyword=esc_html__ \
		--keyword=esc_html_e \
		--keyword=esc_html_x:1,2c \
		--keyword=esc_attr__ \
		--keyword=esc_attr_e \
		--keyword=esc_attr_x:1,2c \
		--add-comments=translators \
		--sort-output \
		--package-name="WPWing Wishlist and Waitlist for WooCommerce" \
		--package-version="$(shell grep "Version:" wpwing-wishlist-and-waitlist-for-woocommerce.php | head -1 | sed 's/.*Version: *//')" \
		--msgid-bugs-address="https://wpwing.com" \
		-o languages/wpwing-wishlist-and-waitlist-for-woocommerce.pot \
		$$(find . -name "*.php" ! -path "./vendor/*" ! -path "./dist/*" | sort)
	@echo "POT file updated: languages/wpwing-wishlist-and-waitlist-for-woocommerce.pot"

## Remove the dist folder
clean:
	rm -rf "$(DIST_DIR)"
