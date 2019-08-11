#############################################################
#
# mtr
#
#############################################################

MTR_VERSION = 0.93
MTR_SITE = ftp://ftp.bitwizard.nl/mtr
MTR_SOURCE = mtr-$(MTR_VERSION).tar.gz

MTR_DEPENDENCIES = host-pkg-config ncurses libcap

MTR_CONF_OPT = \
	PACKAGE_VERSION="$(MTR_VERSION)" \
	PACKAGE_STRING="mtr $(MTR_VERSION)" \
	--without-gtk

define MTR_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/mtr $(TARGET_DIR)/usr/sbin/
	$(INSTALL) -D -m 4711 $(@D)/mtr-packet $(TARGET_DIR)/usr/sbin/
endef

define MTR_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/mtr
	rm -f $(TARGET_DIR)/usr/sbin/mtr-packet
endef

$(eval $(call AUTOTARGETS,package,mtr))
