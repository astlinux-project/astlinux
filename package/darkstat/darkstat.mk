#############################################################
#
# darkstat
#
#############################################################

DARKSTAT_VERSION = 3.0.722
DARKSTAT_SOURCE = darkstat-$(DARKSTAT_VERSION).tar.gz
DARKSTAT_SITE = https://github.com/emikulic/darkstat/archive/$(DARKSTAT_VERSION)

DARKSTAT_DEPENDENCIES = zlib libpcap

# no configure built
DARKSTAT_AUTORECONF = YES

DARKSTAT_UNINSTALL_STAGING_OPT = --version

DARKSTAT_CONF_OPT = \
        --disable-debug

DARKSTAT_MAKE_ENV = \
	HOSTCC="$(HOSTCC)" \
	HOSTCFLAGS="$(HOST_CFLAGS)"

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
