#############################################################
#
# nano
#
#############################################################

NANO_VERSION = 2.5.2
NANO_SITE = http://www.nano-editor.org/dist/v2.5
NANO_MAKE_ENV = CURSES_LIB="-lncurses"
NANO_CONF_ENV = ac_cv_prog_NCURSESW_CONFIG=false
NANO_CONF_OPT = \
	--without-slang \
	--disable-utf8 \
	--enable-tiny

NANO_DEPENDENCIES = ncurses

ifeq ($(BR2_PACKAGE_FILE),y)
	NANO_DEPENDENCIES += file
else
	NANO_CONF_ENV += ac_cv_lib_magic_magic_open=no
endif

define NANO_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 $(@D)/src/nano $(TARGET_DIR)/usr/bin/nano
endef

define NANO_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/nano
endef

$(eval $(call AUTOTARGETS,package,nano))
