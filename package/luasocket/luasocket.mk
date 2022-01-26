#############################################################
#
# luasocket
#
#############################################################

LUASOCKET_VERSION = 3.0-rc1
LUASOCKET_SOURCE = luasocket-$(LUASOCKET_VERSION).tar.gz
LUASOCKET_SITE = https://astlinux-project.org/files
#LUASOCKET_SITE = http://github.com/diegonehab/luasocket/archive
LUASOCKET_DEPENDENCIES = lua

define LUASOCKET_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) -f makefile \
		CC="$(TARGET_CC)" LD="$(TARGET_CC)"
endef

define LUASOCKET_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) -f makefile \
		INSTALL_TOP_LDIR="$(TARGET_DIR)/usr/share/lua" \
		INSTALL_TOP_CDIR="$(TARGET_DIR)/usr/lib/lua" install-unix
endef

define LUASOCKET_UNINSTALL_TARGET_CMDS
	rm -rf "$(TARGET_DIR)/usr/lib/lua/mime"
	rm -rf "$(TARGET_DIR)/usr/lib/lua/socket"
	rm -rf "$(TARGET_DIR)/usr/share/lua/socket"
	rm -f "$(TARGET_DIR)/usr/share/lua/socket.lua"
	rm -f "$(TARGET_DIR)/usr/share/lua/mime.lua"
	rm -f "$(TARGET_DIR)/usr/share/lua/ltn12.lua"
endef

define LUASOCKET_CLEAN_CMDS
	$(MAKE) -C $(@D) -f makefile clean
endef

$(eval $(call GENTARGETS,package,luasocket))
