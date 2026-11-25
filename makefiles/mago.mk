QA_TARGETS := $(QA_TARGETS) fmt-check lint sa

HELP += $(call MK_HELP,'fmt','Format source code with Mago')
fmt:
	@$(DOCKER_RUN) ${DOCKER_IMAGE_NAME} vendor/bin/mago fmt
.PHONY: fmt

HELP += $(call MK_HELP,'fmt-check','Check source code formatting with Mago without modifying any files')
fmt-check:
	@$(DOCKER_RUN) ${DOCKER_IMAGE_NAME} vendor/bin/mago fmt --check
.PHONY: fmt-check

HELP += $(call MK_HELP,'lint','Lint source code with Mago')
lint:
	@$(DOCKER_RUN) ${DOCKER_IMAGE_NAME} vendor/bin/mago lint --fail-on-out-of-sync-baseline
.PHONY: lint

HELP += $(call MK_HELP,'lint-fix','Fix fixable CS violations with mago')
lint-fix:
	@$(DOCKER_RUN) ${DOCKER_IMAGE_NAME} vendor/bin/mago lint --fix --format-after-fix
.PHONY: lint-fix

HELP += $(call MK_HELP,'sa','Run static analysis with Mago')
sa:
	@$(DOCKER_RUN) ${DOCKER_IMAGE_NAME} vendor/bin/mago analyse --fail-on-out-of-sync-baseline
.PHONY: sa

HELP += $(call MK_HELP,'watch','Run static analysis with Mago in watch mode')
watch:
	@$(DOCKER_RUN) ${DOCKER_IMAGE_NAME} vendor/bin/mago analyse --watch
.PHONY: watch

HELP += $(call MK_HELP,'sa-fix','Fix fixable static analysis issues')
sa-fix:
	@$(DOCKER_RUN) ${DOCKER_IMAGE_NAME} vendor/bin/mago analyse --fix --format-after-fix --fail-on-remaining
.PHONY: sa-fix

HELP += $(call MK_HELP,'set-lint-baseline','Expand the current lint baseline')
set-lint-baseline:
	@$(DOCKER_RUN) ${DOCKER_IMAGE_NAME} vendor/bin/mago lint --generate-baseline
.PHONY:set-lint-baseline

HELP += $(call MK_HELP,'update-lint-baseline','Remove fixed issues from the lint baseline')
update-lint-baseline:
	@$(DOCKER_RUN) ${DOCKER_IMAGE_NAME} vendor/bin/mago lint --remove-outdated-baseline-entries
.PHONY:update-lint-baseline

HELP += $(call MK_HELP,'set-sa-baseline','Expand the current static analysis baseline')
set-sa-baseline:
	@$(DOCKER_RUN) ${DOCKER_IMAGE_NAME} vendor/bin/mago analyse --generate-baseline
.PHONY:set-sa-baseline

HELP += $(call MK_HELP,'update-sa-baseline','Remove fixed issues from the static analysis baseline')
update-sa-baseline:
	@$(DOCKER_RUN) ${DOCKER_IMAGE_NAME} vendor/bin/mago analyse --remove-outdated-baseline-entries
.PHONY:update-sa-baseline

HELP += $(call MK_HELP,'update-baseline','Update the SA and Lint baselines')
update-baseline: update-sa-baseline update-lint-baseline
.PHONY:update-baseline

HELP += $(call MK_HELP,'fix','Run all Mago’s fixers')
fix: fmt lint-fix sa-fix
.PHONY: fix
