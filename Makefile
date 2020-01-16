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

# Fetch and initialize dependencies
.PHONY: install
install:
	$(warning Unimplemented)
