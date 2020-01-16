# Be explicit about what shell we want to use
SHELL := bash

# Drop as much of the built in rules as we can
.SUFFIXES:
MAKEFLAGS += --no-builtin-rules

# Give ourselves some extra syntax flexibility
.ONESHELL:
.SECONDEXPANSION:

# Found the full path of where we are, including following symlinks
PROJECTDIR := $(dir $(realpath Makefile))

# Fist valid target is default, lets be explicit
default: all

# A target to get everything ship shape for deployment
all: install

# Node related make rules to make sure deploys are fresh
# see https://stackoverflow.com/a/44226605/313192
MANIFEST_DIR := .manifest
LAST_MANIFEST := $(MANIFEST_DIR)/node_modules.last
NEW_MANIFEST := $(MANIFEST_DIR)/node_modules.peek
GEN_MANIFEST := find node_modules/ -exec stat -c '%n %y' {} \;

$(shell mkdir -p $(MANIFEST_DIR) node_modules)
$(if $(wildcard $(LAST_MANIFEST)),,$(shell touch $(LAST_MANIFEST)))
$(shell $(GEN_MANIFEST) > $(NEW_MANIFEST))
$(shell cmp -s $(LAST_MANIFEST) $(NEW_MANIFEST) || touch node_modules)

package-lock.json: node_modules package.json
	npm install
	touch -mr $< $@

$(LAST_MANIFEST): package-lock.json
	$(GEN_MANIFEST) > $@

# What to do to restore everything back to pristine condition
clean:
	rm -rf node_modules $(MANIFEST_DIR)

# Fetch and initialize dependencies
.PHONY: install
install: $(LAST_MANIFEST) npm_lib_links

NPM_LIBS = codemirror fork-awesome highlight.js lightbox2 jquery

.PHONY: npm_lib_links
npm_lib_links: $(foreach LIB,$(NPM_LIBS),get/lib/$(LIB))

get/lib/%: | package-lock.json
	ln -sfrT node_modules/$* $@
