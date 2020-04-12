################################################################################
#
# nasm
#
################################################################################

NASM_VERSION = 2.14.02
NASM_SOURCE = nasm-$(NASM_VERSION).tar.xz
NASM_SITE = https://www.nasm.us/pub/nasm/releasebuilds/$(NASM_VERSION)

$(eval $(call AUTOTARGETS,package,nasm,host))
