#############################################################
#
# dialog
#
#############################################################
DIALOG_VERSION = 1.3-20170509
DIALOG_SOURCE = dialog-$(DIALOG_VERSION).tgz
DIALOG_SITE = ftp://ftp.invisible-island.net/dialog
DIALOG_DEPENDENCIES = host-pkg-config ncurses

DIALOG_CONF_OPT = \
	--with-ncurses \
	--with-curses-dir=$(STAGING_DIR)/usr \
	--disable-rpath-hack \
	NCURSES_CONFIG=$(STAGING_DIR)/usr/bin/ncurses5-config

ifneq ($(BR2_ENABLE_LOCALE),y)
DIALOG_DEPENDENCIES += libiconv
endif

define DIALOG_INSTALL_TARGET_CMDS
	install -c $(@D)/dialog $(TARGET_DIR)/usr/bin/dialog
endef

define DIALOG_POST_CLEAN
	-$(MAKE) -C $(@D) clean
	rm -f $(TARGET_DIR)/usr/bin/dialog
endef

$(eval $(call AUTOTARGETS,package,dialog))
