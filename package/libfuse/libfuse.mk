################################################################################
#
# libfuse
#
################################################################################

LIBFUSE_VERSION = 2.9.9
LIBFUSE_SOURCE = fuse-$(LIBFUSE_VERSION).tar.gz
LIBFUSE_SITE = https://github.com/libfuse/libfuse/releases/download/fuse-$(LIBFUSE_VERSION)
LIBFUSE_INSTALL_STAGING = YES
LIBFUSE_DEPENDENCIES = $(if $(BR2_PACKAGE_LIBICONV),libiconv)
LIBFUSE_CONF_OPT = \
	--disable-example \
	--enable-lib \
	--disable-util \
	UDEV_RULES_PATH=/usr/lib/udev/rules.d

define LIBFUSE_INSTALL_TARGET_CMDS
	cp -dpf $(STAGING_DIR)/usr/lib/libfuse.so* $(TARGET_DIR)/usr/lib/
endef

define LIBFUSE_CLEAN_CMDS
	## Ignore clean
endef

define LIBFUSE_UNINSTALL_STAGING_CMDS
	## Ignore uninstall staging
endef

define LIBFUSE_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/libfuse.so*
endef

$(eval $(call AUTOTARGETS,package,libfuse))
