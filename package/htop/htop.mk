#############################################################
#
# htop
#
#############################################################

HTOP_VERSION = 2.2.0
HTOP_SOURCE = htop-$(HTOP_VERSION).tar.gz
HTOP_SITE = https://hisham.hm/htop/releases/$(HTOP_VERSION)
HTOP_DEPENDENCIES = ncurses
# Prevent htop build system from searching the host paths
HTOP_CONF_ENV = HTOP_NCURSES_CONFIG_SCRIPT=$(STAGING_DIR)/usr/bin/$(NCURSES_CONFIG_SCRIPTS)

HTOP_CONF_OPT = \
	--disable-unicode

define HTOP_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/htop $(TARGET_DIR)/usr/bin/htop
endef

define HTOP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/htop
endef

$(eval $(call AUTOTARGETS,package,htop))
