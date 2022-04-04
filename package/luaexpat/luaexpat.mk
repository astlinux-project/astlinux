#############################################################
#
# luaexpat
#
#############################################################

LUAEXPAT_VERSION = 1.4.1
LUAEXPAT_SITE = https://github.com/lunarmodules/luaexpat/archive/$(LUAEXPAT_VERSION)
LUAEXPAT_DEPENDENCIES = lua expat

LUAEXPAT_MFLAGS += LUA_LDIR=$(TARGET_DIR)/usr/share/lua
LUAEXPAT_MFLAGS += LUA_CDIR=$(TARGET_DIR)/usr/lib/lua
LUAEXPAT_MFLAGS += LUA_INC=-I$(STAGING_DIR)/usr/include
LUAEXPAT_MFLAGS += EXPAT_INC=-I$(STAGING_DIR)/usr/include
LUAEXPAT_MFLAGS += CC="$(TARGET_CC) -fPIC $(TARGET_CFLAGS)"

define LUAEXPAT_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) $(LUAEXPAT_MFLAGS)
endef

define LUAEXPAT_INSTALL_TARGET_CMDS
	$(INSTALL) -D $(@D)/src/lxp.so $(TARGET_DIR)/usr/lib/lua/lxp.so
	$(INSTALL) -D -m 0644 $(@D)/src/lxp/lom.lua $(TARGET_DIR)/usr/share/lua/lxp/lom.lua
endef

define LUAEXPAT_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/lua/lxp.so
	rm -f $(TARGET_DIR)/usr/share/lua/lxp/lom.lua
endef

define LUAEXPAT_CLEAN_CMDS
	$(MAKE) -C $(@D) $(LUAEXPAT_MFLAGS) clean
endef

$(eval $(call GENTARGETS,package,luaexpat))
