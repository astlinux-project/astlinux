#############################################################
#
# libsodium
#
#############################################################
LIBSODIUM_VERSION = 0.5.0
LIBSODIUM_SOURCE = libsodium-$(LIBSODIUM_VERSION).tar.gz
LIBSODIUM_SITE = http://download.dnscrypt.org/libsodium/releases

LIBSODIUM_INSTALL_STAGING = YES

define LIBSODIUM_INSTALL_TARGET_CMDS
	cp -a $(STAGING_DIR)/usr/lib/libsodium.so* $(TARGET_DIR)/usr/lib/
endef

define LIBSODIUM_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/libsodium.so*
endef

$(eval $(call AUTOTARGETS,package,libsodium))
