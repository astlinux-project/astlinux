#############################################################
#
# darkstat
#
#############################################################
DARKSTAT_VERSION = 3.0.717
DARKSTAT_SITE = http://unix4lyfe.org/darkstat
DARKSTAT_SOURCE = darkstat-$(DARKSTAT_VERSION).tar.bz2

DARKSTAT_DEPENDENCIES = zlib libpcap

DARKSTAT_UNINSTALL_STAGING_OPT = --version

DARKSTAT_CONF_OPT = \
        --disable-debug \
        --with-chroot-dir=/var/empty

define DARKSTAT_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/darkstat $(TARGET_DIR)/usr/sbin/
	$(INSTALL) -D -m 0755 package/darkstat/darkstat.init $(TARGET_DIR)/etc/init.d/darkstat
	ln -sf ../../init.d/darkstat $(TARGET_DIR)/etc/runlevels/default/S75darkstat
	ln -sf ../../init.d/darkstat $(TARGET_DIR)/etc/runlevels/default/K15darkstat
endef

define DARKSTAT_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/darkstat
	rm -f $(TARGET_DIR)/etc/init.d/darkstat
	rm -f $(TARGET_DIR)/etc/runlevels/default/S75darkstat
	rm -f $(TARGET_DIR)/etc/runlevels/default/K15darkstat
endef

$(eval $(call AUTOTARGETS,package,darkstat))
