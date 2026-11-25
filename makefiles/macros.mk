# Formatting Macros
MK_BLUE = echo -e "\033[34m"$(1)"\033[0m"
MK_GREEN = echo -e "\033[32m"$(1)"\033[0m"
MK_RED = echo -e "\e[31m"$(1)"\e[0m"
MK_HELP = "\033[36m"$(1)"\033[0m "$(2)"\n"
MK_ADVICE = "\033[34m"$(1)"\033[0m "$(2)"\n"

MK_INFO = @$(call MK_BLUE, $1)
MK_SUCCESS = @$(call MK_GREEN, $1)
MK_ERROR = @$(call MK_RED, $1)

# Variables for collecting targets
QA_TARGETS :=
CLEAN_TARGETS :=

# The $(HELP) variable collects help text for the `help` target
HELP := ""
HELP += $(call MK_HELP,'help','Show this help')
HELP += $(call MK_HELP,'qa','Run all qa targets')
HELP += $(call MK_HELP,'clean','Run various clean up jobs')
