#############################################################
#
# libsodium
#
#############################################################
LIBSODIUM_VERSION = 0.4.5
LIBSODIUM_SOURCE = libsodium-$(LIBSODIUM_VERSION).tar.gz
#LIBSODIUM_SITE = http://github.com/jedisct1/libsodium/releases/download/$(LIBSODIUM_VERSION)
LIBSODIUM_SITE = http://files.astlinux.org

LIBSODIUM_INSTALL_STAGING = YES

define LIBSODIUM_INSTALL_TARGET_CMDS
	cp -a $(STAGING_DIR)/usr/lib/libsodium.so* $(TARGET_DIR)/usr/lib/
endef

define LIBSODIUM_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/libsodium.so*
endef

$(eval $(call AUTOTARGETS,package,libsodium))
