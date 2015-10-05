#############################################################
#
# PCIUTILS
#
#############################################################

PCIUTILS_VERSION = 3.4.0
PCIUTILS_SITE = ftp://atrey.karlin.mff.cuni.cz/pub/linux/pci
PCIUTILS_INSTALL_STAGING = YES
# Depend on linux to define LINUX_VERSION_PROBED
PCIUTILS_DEPENDENCIES = linux

PCIUTILS_ZLIB=no
PCIUTILS_DNS=no
PCIUTILS_SHARED=yes
PCIUTILS_KMOD=no
PCIUTILS_HWDB=no

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
	$(TARGET_MAKE_ENV) $(MAKE) CC="$(TARGET_CC)" \
		HOST="$(KERNEL_ARCH)-linux" \
		OPT="$(TARGET_CFLAGS)" \
		LDFLAGS="$(TARGET_LDFLAGS)" \
		RANLIB=$(TARGET_RANLIB) \
		AR=$(TARGET_AR) \
		-C $(PCIUTILS_DIR) \
		SHARED=$(PCIUTILS_SHARED) \
		ZLIB=$(PCIUTILS_ZLIB) \
		DNS=$(PCIUTILS_DNS) \
		LIBKMOD=$(PCIUTILS_KMOD) \
		HWDB=$(PCIUTILS_HWDB) \
		PREFIX=/usr
endef

# Ditch install-lib if SHARED is an option in the future
define PCIUTILS_INSTALL_TARGET_CMDS
	$(MAKE1) BUILDDIR=$(@D) -C $(@D) PREFIX=$(TARGET_DIR)/usr \
		SHARED=$(PCIUTILS_SHARED) install install-lib
	chmod 755 $(TARGET_DIR)/usr/lib/libpci.so.$(PCIUTILS_VERSION) # set permissions so it is stripped
endef

define PCIUTILS_INSTALL_STAGING_CMDS
	$(MAKE1) BUILDDIR=$(@D) -C $(@D) PREFIX=$(STAGING_DIR)/usr \
		SHARED=$(PCIUTILS_SHARED) install install-lib
endef

$(eval $(call GENTARGETS,package,pciutils))
