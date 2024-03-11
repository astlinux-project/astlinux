#############################################################
#
# unbound
#
#############################################################

UNBOUND_VERSION = 1.19.2
UNBOUND_SITE = https://nlnetlabs.nl/downloads/unbound
UNBOUND_SOURCE = unbound-$(UNBOUND_VERSION).tar.gz
UNBOUND_INSTALL_STAGING = YES

UNBOUND_DEPENDENCIES = host-bison host-flex openssl expat

UNBOUND_CONF_OPT = \
	--disable-rpath \
	--disable-debug \
	--with-conf-file=/etc/unbound/unbound.conf \
	--with-pidfile=/var/run/unbound/unbound.pid \
	--with-rootkey-file=/etc/unbound/root.key \
	--enable-tfo-server \
	--enable-tfo-client \
	--with-libexpat="$(STAGING_DIR)/usr" \
	--with-ssl="$(STAGING_DIR)/usr"

define UNBOUND_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(STAGING_DIR)/usr/sbin/unbound $(TARGET_DIR)/usr/sbin/unbound
	$(INSTALL) -m 0755 -D $(STAGING_DIR)/usr/sbin/unbound-host $(TARGET_DIR)/usr/sbin/unbound-host
	ln -sf ../sbin/unbound-host $(TARGET_DIR)/usr/bin/host
	cp -a $(STAGING_DIR)/usr/lib/libunbound.so* $(TARGET_DIR)/usr/lib/
	ln -sf /tmp/etc/unbound $(TARGET_DIR)/etc/unbound
	$(INSTALL) -D -m 0755 package/unbound/unbound.init $(TARGET_DIR)/etc/init.d/unbound
	ln -sf ../../init.d/unbound $(TARGET_DIR)/etc/runlevels/default/S18unbound
	ln -sf ../../init.d/unbound $(TARGET_DIR)/etc/runlevels/default/K15unbound
endef

define UNBOUND_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/unbound
	rm -f $(TARGET_DIR)/usr/sbin/unbound-host
	rm -f $(TARGET_DIR)/usr/bin/host
	rm -f $(TARGET_DIR)/usr/lib/libunbound.so*
	rm -f $(TARGET_DIR)/etc/unbound
	rm -f $(TARGET_DIR)/etc/init.d/unbound
	rm -f $(TARGET_DIR)/etc/runlevels/default/S18unbound
	rm -f $(TARGET_DIR)/etc/runlevels/default/K15unbound
endef

$(eval $(call AUTOTARGETS,package,unbound))
