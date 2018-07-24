################################################################################
#
# keepalived
#
################################################################################

KEEPALIVED_VERSION = 2.0.6
KEEPALIVED_SOURCE = keepalived-$(KEEPALIVED_VERSION).tar.gz
KEEPALIVED_SITE = http://www.keepalived.org/software
KEEPALIVED_DEPENDENCIES = host-pkg-config linux openssl

KEEPALIVED_CONF_OPT = \
	--disable-dbus \
	--disable-libnl \
	--disable-libipset \
	--disable-libiptc \
	--with-init=SYSV

define KEEPALIVED_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/keepalived/keepalived $(TARGET_DIR)/usr/sbin/keepalived
	$(INSTALL) -m 0755 -D package/keepalived/keepalived.init $(TARGET_DIR)/etc/init.d/keepalived
	ln -sf /tmp/etc/keepalived.conf $(TARGET_DIR)/etc/keepalived.conf
	ln -sf ../../init.d/keepalived $(TARGET_DIR)/etc/runlevels/default/S04keepalived
	ln -sf ../../init.d/keepalived $(TARGET_DIR)/etc/runlevels/default/K89keepalived
endef

define KEEPALIVED_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/keepalived
	rm -f $(TARGET_DIR)/etc/init.d/keepalived
	rm -f $(TARGET_DIR)/etc/keepalived.conf
	rm -f $(TARGET_DIR)/etc/runlevels/default/S04keepalived
	rm -f $(TARGET_DIR)/etc/runlevels/default/K89keepalived
endef

$(eval $(call AUTOTARGETS,package,keepalived))
