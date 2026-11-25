.DEFAULT_GOAL ?= help

ifneq ("$(wildcard .env)","")
    include .env
endif

include makefiles/macros.mk
include makefiles/docker.mk
include makefiles/mago.mk

HELP += $(call MK_HELP,'migrate','Run the migration')
migrate:
	@$(DOCKER_RUN) ${DOCKER_IMAGE_NAME} php run.php
.PHONY: migrate

vendor: docker-build
	@$(DOCKER_RUN) ${DOCKER_IMAGE_NAME} composer install

HELP += $(call MK_HELP,'install','Install PHP dependencies')
install: vendor
.PHONY: install

clean: $(CLEAN_TARGETS)
.PHONY: clean

qa: $(QA_TARGETS)
.PHONY: qa

help:
	@echo $(HELP)
.PHONY: help
