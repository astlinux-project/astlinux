#############################################################
#
# mtr
#
#############################################################
MTR_VERSION = 0.86
MTR_SITE = ftp://ftp.bitwizard.nl/mtr
MTR_SOURCE = mtr-$(MTR_VERSION).tar.gz
MTR_AUTORECONF = YES

MTR_DEPENDENCIES = host-pkg-config ncurses

MTR_CONF_OPT = \
	--without-gtk

define MTR_INSTALL_TARGET_CMDS
	$(INSTALL) -D $(@D)/mtr $(TARGET_DIR)/usr/bin/
endef

define MTR_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/mtr
endef

$(eval $(call AUTOTARGETS,package,mtr))
