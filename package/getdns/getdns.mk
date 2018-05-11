#############################################################
#
# getdns
#
#############################################################

GETDNS_VERSION = 1.4.2
GETDNS_SITE = https://getdnsapi.net/dist
GETDNS_SOURCE = getdns-$(GETDNS_VERSION).tar.gz
GETDNS_INSTALL_STAGING = YES

GETDNS_DEPENDENCIES = host-m4 openssl libidn libyaml

GETDNS_CONF_OPT = \
	--disable-rpath \
	--without-libidn2 \
	--with-piddir=/var/run/stubby \
	--with-ssl="$(STAGING_DIR)/usr" \
	--with-libidn="$(STAGING_DIR)/usr" \
	--with-libyaml="$(STAGING_DIR)/usr" \
	--with-stubby

ifeq ($(BR2_PACKAGE_UNBOUND),y)
GETDNS_DEPENDENCIES += unbound
GETDNS_CONF_OPT += --with-libunbound="$(STAGING_DIR)/usr"
else
GETDNS_CONF_OPT += --enable-stub-only
endif

# GOST cipher support requires openssl extra engines
ifeq ($(BR2_PACKAGE_OPENSSL_ENGINES),y)
GETDNS_CONF_OPT += --enable-gost
else
GETDNS_CONF_OPT += --disable-gost
endif

define GETDNS_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(STAGING_DIR)/usr/bin/stubby $(TARGET_DIR)/usr/bin/stubby
	$(INSTALL) -m 0755 -D $(STAGING_DIR)/usr/bin/getdns_server_mon $(TARGET_DIR)/usr/bin/getdns_server_mon
	cp -a $(STAGING_DIR)/usr/lib/libgetdns.so* $(TARGET_DIR)/usr/lib/
	ln -sf /tmp/etc/stubby $(TARGET_DIR)/etc/stubby
	$(INSTALL) -D -m 0755 package/getdns/stubby.init $(TARGET_DIR)/etc/init.d/stubby
	ln -sf ../../init.d/stubby $(TARGET_DIR)/etc/runlevels/default/S18stubby
	ln -sf ../../init.d/stubby $(TARGET_DIR)/etc/runlevels/default/K15stubby
endef

define GETDNS_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/stubby
	rm -f $(TARGET_DIR)/usr/bin/getdns_server_mon
	rm -f $(TARGET_DIR)/usr/lib/libgetdns.so*
	rm -f $(TARGET_DIR)/etc/stubby
	rm -f $(TARGET_DIR)/etc/runlevels/default/S18stubby
	rm -f $(TARGET_DIR)/etc/runlevels/default/K15stubby
endef

$(eval $(call AUTOTARGETS,package,getdns))
