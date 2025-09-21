################################################################################
#
# unionfs (fuse)
#
################################################################################

UNIONFS_VERSION = 3.7
UNIONFS_SOURCE = unionfs-fuse-$(UNIONFS_VERSION).tar.gz
UNIONFS_SITE = https://github.com/rpodgorny/unionfs-fuse/archive/v$(UNIONFS_VERSION)

UNIONFS_DEPENDENCIES = host-pkg-config libfuse

define UNIONFS_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) \
		CC="$(TARGET_CC)" \
		AR="$(TARGET_AR)" \
		CFLAGS="$(TARGET_CFLAGS) -Wall -fPIC" \
	-C $(@D)/src
endef

define UNIONFS_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/src/unionfs $(TARGET_DIR)/usr/bin/unionfs
	$(INSTALL) -m 0755 -D $(@D)/src/unionfsctl $(TARGET_DIR)/usr/bin/unionfsctl
endef

define UNIONFS_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/unionfs
	rm -f $(TARGET_DIR)/usr/bin/unionfsctl
endef

$(eval $(call GENTARGETS,package,unionfs))
