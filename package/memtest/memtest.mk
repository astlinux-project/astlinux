#############################################################
#
# memtest
#
#############################################################

MEMTEST_VERSION = 5.01
MEMTEST_SOURCE = memtest86+-$(MEMTEST_VERSION).tar.gz
MEMTEST_SITE = http://www.memtest.org/download/$(MEMTEST_VERSION)

# memtest86+ is sensitive to toolchain changes, use the shipped binary version
# Install memtest.bin into the build tree and build-runnix will install it
define MEMTEST_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/precomp.bin $(@D)/memtest.bin
endef

$(eval $(call GENTARGETS,package,memtest))
