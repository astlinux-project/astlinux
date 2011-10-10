#############################################################
#
# nano
#
#############################################################

NANO_VERSION = 2.2.6
NANO_SITE = http://www.nano-editor.org/dist/v2.2
NANO_MAKE_ENV = CURSES_LIB="-lncurses"
NANO_DEPENDENCIES = ncurses
NANO_CONF_OPT = \
	--without-slang \
	--enable-tiny

define NANO_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 $(@D)/src/nano $(TARGET_DIR)/usr/bin/nano
	#$(INSTALL) -m 444 -D package/nano/etc.nanorc $(TARGET_DIR)/etc/nanorc
	#$(INSTALL) -m 444 -D package/nano/asterisk.nanorc $(TARGET_DIR)/usr/share/nano/asterisk.nanorc
endef

define NANO_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/nano
endef

$(eval $(call AUTOTARGETS,package,nano))
