################################################################################
#
# keepalived
#
################################################################################

KEEPALIVED_VERSION = 2.0.20
KEEPALIVED_SOURCE = keepalived-$(KEEPALIVED_VERSION).tar.gz
KEEPALIVED_SITE = http://www.keepalived.org/software
KEEPALIVED_DEPENDENCIES = host-pkg-config linux openssl

KEEPALIVED_CONF_OPT = \
	--disable-warnings \
	--disable-log-file \
	--disable-dbus \
	--disable-libnl \
	--disable-libiptc \
	--disable-track-process \
	--disable-linkbeat \
	--with-run-dir=/var \
	--with-init=SYSV

define KEEPALIVED_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/keepalived/keepalived $(TARGET_DIR)/usr/sbin/keepalived
	$(INSTALL) -m 0755 -D package/keepalived/keepalived.init $(TARGET_DIR)/etc/init.d/keepalived
	ln -sf /tmp/etc/keepalived $(TARGET_DIR)/etc/keepalived
	ln -sf ../../init.d/keepalived $(TARGET_DIR)/etc/runlevels/default/S12keepalived
	ln -sf ../../init.d/keepalived $(TARGET_DIR)/etc/runlevels/default/K28keepalived
endef

define KEEPALIVED_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/keepalived
	rm -f $(TARGET_DIR)/etc/init.d/keepalived
	rm -f $(TARGET_DIR)/etc/keepalived
	rm -f $(TARGET_DIR)/etc/runlevels/default/S12keepalived
	rm -f $(TARGET_DIR)/etc/runlevels/default/K28keepalived
endef

$(eval $(call AUTOTARGETS,package,keepalived))
