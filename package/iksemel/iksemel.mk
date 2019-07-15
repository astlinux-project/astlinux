#############################################################
#
# iksemel
#
#############################################################

IKSEMEL_VERSION = 1.4
IKSEMEL_SOURCE = iksemel-$(IKSEMEL_VERSION).tar.gz
IKSEMEL_SITE = https://s3.amazonaws.com/files.astlinux-project

IKSEMEL_INSTALL_STAGING = YES

define IKSEMEL_INSTALL_TARGET_CMDS
        cp -a $(STAGING_DIR)/usr/lib/libiksemel.so* $(TARGET_DIR)/usr/lib/
endef

define IKSEMEL_UNINSTALL_TARGET_CMDS
        rm -f $(TARGET_DIR)/usr/lib/libiksemel.so*
endef

$(eval $(call AUTOTARGETS,package,iksemel))
