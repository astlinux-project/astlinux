#############################################################
#
# vnstat
#
#############################################################

VNSTAT_VERSION = 2.11
VNSTAT_SITE = https://humdi.net/vnstat
VNSTAT_DEPENDENCIES = host-pkg-config sqlite
VNSTAT_CONF_OPTS = \
	--disable-extra-paths \
	--disable-image-output

define VNSTAT_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/vnstat $(TARGET_DIR)/usr/bin/vnstat
	$(INSTALL) -D -m 0755 $(@D)/vnstatd $(TARGET_DIR)/usr/sbin/vnstatd
	$(INSTALL) -D -m 0755 package/vnstat/vnstat.init $(TARGET_DIR)/etc/init.d/vnstat
	ln -sf /tmp/etc/vnstat.conf $(TARGET_DIR)/etc/vnstat.conf
	ln -sf ../../init.d/vnstat $(TARGET_DIR)/etc/runlevels/default/S98vnstat
	ln -sf ../../init.d/vnstat $(TARGET_DIR)/etc/runlevels/default/K01vnstat
endef

define VNSTAT_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/vnstat
	rm -f $(TARGET_DIR)/usr/sbin/vnstatd
	rm -f $(TARGET_DIR)/etc/init.d/vnstat
	rm -f $(TARGET_DIR)/etc/vnstat.conf
	rm -f $(TARGET_DIR)/etc/runlevels/default/S98vnstat
	rm -f $(TARGET_DIR)/etc/runlevels/default/K01vnstat
endef

$(eval $(call AUTOTARGETS,package,vnstat))
