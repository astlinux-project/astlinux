#############################################################
#
# iksemel
#
#############################################################

IKSEMEL_VERSION = 1.4
IKSEMEL_SOURCE = iksemel-$(IKSEMEL_VERSION).tar.gz
#Ref: http://deb.debian.org/debian/pool/main/libi/libiksemel/libiksemel_1.4.orig.tar.gz
IKSEMEL_SITE = https://astlinux-project.org/files
IKSEMEL_DEPENDENCIES = host-pkg-config
# patch configure.ac
IKSEMEL_AUTORECONF = YES

IKSEMEL_INSTALL_STAGING = YES

ifeq ($(BR2_PACKAGE_GNUTLS),y)
IKSEMEL_DEPENDENCIES += gnutls
endif

define IKSEMEL_INSTALL_TARGET_CMDS
        cp -a $(STAGING_DIR)/usr/lib/libiksemel.so* $(TARGET_DIR)/usr/lib/
endef

define IKSEMEL_UNINSTALL_TARGET_CMDS
        rm -f $(TARGET_DIR)/usr/lib/libiksemel.so*
endef

$(eval $(call AUTOTARGETS,package,iksemel))
