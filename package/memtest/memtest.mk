#############################################################
#
# memtest (memtest86+)
#
#############################################################

MEMTEST_VERSION = 5.01
MEMTEST_SOURCE = memtest86+-$(MEMTEST_VERSION).tar.gz
MEMTEST_SITE = http://www.memtest.org/download/$(MEMTEST_VERSION)

## Host build on Debian 10
## sudo apt-get update
## sudo apt-get install libc6-i386
## sudo apt-get install libc6-dev-i386

define MEMTEST_BUILD_CMDS
	$(HOST_MAKE_ENV) $(MAKE) -C $(@D)
endef

define MEMTEST_INSTALL_TARGET_CMDS
	## The build-runnix script will install memtest.bin on the RUNNIX image as memtest
endef

$(eval $(call GENTARGETS,package,memtest))
