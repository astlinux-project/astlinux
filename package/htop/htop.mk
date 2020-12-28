#############################################################
#
# htop
#
#############################################################

HTOP_VERSION = 3.0.4
HTOP_SOURCE = htop-$(HTOP_VERSION).tar.gz
HTOP_SITE = https://github.com/htop-dev/htop/archive/$(HTOP_VERSION)
HTOP_DEPENDENCIES = ncurses

define HTOP_POST_EXTRACT_FIX
	mkdir -p $(@D)/m4
endef
HTOP_POST_EXTRACT_HOOKS += HTOP_POST_EXTRACT_FIX
HTOP_AUTORECONF = YES

# Prevent htop build system from searching the host paths
HTOP_CONF_ENV = HTOP_NCURSES_CONFIG_SCRIPT=$(STAGING_DIR)/usr/bin/$(NCURSES_CONFIG_SCRIPTS)

HTOP_CONF_OPT = \
	--disable-unicode \
	--enable-linux-affinity

ifeq ($(BR2_PACKAGE_LM_SENSORS),y)
HTOP_DEPENDENCIES += lm-sensors
HTOP_CONF_OPT += --with-sensors
else
HTOP_CONF_OPT += --without-sensors
endif

define HTOP_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/htop $(TARGET_DIR)/usr/bin/htop
endef

define HTOP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/htop
endef

$(eval $(call AUTOTARGETS,package,htop))
