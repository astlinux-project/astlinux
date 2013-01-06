#############################################################
#
# luasec
#
#############################################################

LUASEC_VERSION = 0.4.1
LUASEC_SOURCE = luasec-$(LUASEC_VERSION).tar.gz
LUASEC_SITE = http://files.astlinux.org
LUASEC_DEPENDENCIES = lua openssl luasocket

define LUASEC_BUILD_CMDS
	$(MAKE) -C $(@D)/src linux \
		INCDIR="-I$(STAGING_DIR)/usr/include/" \
		LIBDIR="-L$(STAGING_DIR)/usr/lib/" \
		CFLAGS="$(TARGET_CFLAGS) -fpic" \
		CC="$(TARGET_CC)" \
		LD="$(TARGET_LD) -shared"
endef

define LUASEC_INSTALL_TARGET_CMDS
	$(MAKE) -C $(@D)/src \
		LUACPATH="$(TARGET_DIR)/usr/lib/lua" \
		LUAPATH="$(TARGET_DIR)/usr/share/lua" install
endef

define LUASEC_UNINSTALL_TARGET_CMDS
	rm -rf "$(TARGET_DIR)/usr/share/lua/ssl"
	rm -f "$(TARGET_DIR)/usr/share/lua/ssl.lua"
	rm -f "$(TARGET_DIR)/usr/lib/lua/ssl.so"
endef

define LUASEC_CLEAN_CMDS
	$(MAKE) -C $(@D) -f makefile clean
endef

$(eval $(call GENTARGETS,package,luasec))
