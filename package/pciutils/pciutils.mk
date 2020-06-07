#############################################################
#
# PCIUTILS
#
#############################################################

PCIUTILS_VERSION = 3.7.0
PCIUTILS_SITE = $(BR2_KERNEL_MIRROR)/software/utils/pciutils
PCIUTILS_SOURCE = pciutils-$(PCIUTILS_VERSION).tar.xz
PCIUTILS_INSTALL_STAGING = YES
# Depend on linux to define LINUX_VERSION_PROBED
PCIUTILS_DEPENDENCIES = linux

PCIUTILS_MAKE_OPTS = \
	CC="$(TARGET_CC)" \
	HOST="$(KERNEL_ARCH)-linux" \
	OPT="$(TARGET_CFLAGS)" \
	LDFLAGS="$(TARGET_LDFLAGS)" \
	RANLIB=$(TARGET_RANLIB) \
	AR=$(TARGET_AR)

PCIUTILS_MAKE_OPTS += HWDB=no
PCIUTILS_MAKE_OPTS += ZLIB=no
PCIUTILS_MAKE_OPTS += LIBKMOD=no
PCIUTILS_MAKE_OPTS += SHARED=yes
PCIUTILS_MAKE_OPTS += DNS=no

# Build after busybox since it's got a lightweight lspci
ifeq ($(BR2_PACKAGE_BUSYBOX),y)
PCIUTILS_DEPENDENCIES += busybox
endif

define PCIUTILS_CONFIGURE_CMDS
	$(SED) 's/wget --no-timestamping/wget/' $(PCIUTILS_DIR)/update-pciids.sh
	$(SED) 's/uname -s/echo Linux/' \
		-e 's/uname -r/echo $(LINUX_VERSION_PROBED)/' \
		$(PCIUTILS_DIR)/lib/configure
	$(SED) 's/^STRIP/#STRIP/' $(PCIUTILS_DIR)/Makefile
endef

define PCIUTILS_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) $(PCIUTILS_MAKE_OPTS) \
		PREFIX=/usr
endef

define PCIUTILS_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE1) -C $(@D) $(PCIUTILS_MAKE_OPTS) \
		PREFIX=$(TARGET_DIR)/usr \
		install install-lib
	chmod 755 $(TARGET_DIR)/usr/lib/libpci.so.$(PCIUTILS_VERSION)
	rm -f $(TARGET_DIR)/usr/sbin/update-pciids
endef

define PCIUTILS_INSTALL_STAGING_CMDS
	$(TARGET_MAKE_ENV) $(MAKE1) -C $(@D) $(PCIUTILS_MAKE_OPTS) \
		PREFIX=$(STAGING_DIR)/usr \
		install install-lib
endef

$(eval $(call GENTARGETS,package,pciutils))
