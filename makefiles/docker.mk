#
# Make targets for building and using a generic PHP Docker image.
# These targets are foundational for most other QA features.
#
DOCKERFILE ?= Dockerfile
DOCKER_IMAGE_NAME ?= local/harvest-export
# This is the image ID of the built Docker image
DOCKER_IMAGE_ID := $(shell docker images -q ${DOCKER_IMAGE_NAME} | xargs)
DOCKER_DEFAULT_SECURITY_OPS=--cap-drop=ALL --security-opt="no-new-privileges=true"
DOCKER_COMMON_OPS:=-v "`pwd`:`pwd`" -w "`pwd`" -v "`pwd`/.git:`pwd`/.git:ro" --ulimit nofile=1000000
# Mount the local ini file in the container by default, if it exists
ifneq ("$(wildcard php.ini)","")
    DOCKER_COMMON_OPS += -v "`pwd`/php.ini:/usr/local/etc/php/conf.d/999-customisations.ini:ro"
endif

ifneq ("$(wildcard .env)","")
    DOCKER_COMMON_OPS +=  --env-file=.env
endif

DOCKER_RUN:=docker run --rm -it ${DOCKER_DEFAULT_SECURITY_OPS} ${DOCKER_COMMON_OPS}

DOCKER_IMAGE_BUILT := TRUE
ifeq ($(strip $(DOCKER_IMAGE_ID)),)
    DOCKER_IMAGE_BUILT := FALSE
    HELP += $(call MK_HELP,'docker-build','Build the PHP image if not already built')
endif

# Show the rebuild target in help when the image _is_ available
ifeq ("$(DOCKER_IMAGE_BUILT)","TRUE")
    HELP += $(call MK_HELP,'docker-rebuild','Rebuild the PHP Docker container image')
endif

docker-build:
ifeq ($(strip $(DOCKER_IMAGE_ID)),)
docker-build: docker-rebuild
endif
.PHONY: docker-build

docker-rebuild:
	@$(call MK_INFO,"Building the PHP Docker Image")
	@echo "PHP Version: ${PHP_VERSION}"
	@echo "Extensions: ${PHP_EXTENSIONS}"
	docker build -f ${DOCKERFILE} \
	--build-arg PHP_VERSION="${PHP_VERSION}" \
	--build-arg PHP_EXTENSIONS=${PHP_EXTENSIONS} \
	-t ${DOCKER_IMAGE_NAME} .
.PHONY: docker-rebuild

remove-docker-image:
ifneq ($(strip $(DOCKER_IMAGE_ID)),)
	@$(call MK_INFO,"Removing the PHP Docker Image")
	docker image rm ${DOCKER_IMAGE_ID}
endif
.PHONY: remove-docker-image

HELP += $(call MK_HELP,'shell','Get a shell running in the PHP Docker container')
shell: docker-build
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} bash
.PHONY: shell
