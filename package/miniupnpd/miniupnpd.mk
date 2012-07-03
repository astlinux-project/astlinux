#############################################################
#
# miniupnpd
#
#############################################################

MINIUPNPD_VERSION = 1.7
MINIUPNPD_SOURCE = miniupnpd-$(MINIUPNPD_VERSION).tar.gz
MINIUPNPD_SITE = http://miniupnp.free.fr/files
MINIUPNPD_DEPENDENCIES = iptables

define MINIUPNPD_CONFIGURE_CMDS
# add this to make for IPv6... CONFIG_OPTIONS="--ipv6"
	echo -n "AstLinux/" >$(@D)/os.astlinux
	cat $(RUNFS_DIR)/os/ver | tr \(\)\  _ >>$(@D)/os.astlinux
	$(MAKE) CC="$(TARGET_CC)" LD="$(TARGET_LD)" CFLAGS="$(TARGET_CFLAGS)" \
		-f Makefile.linux -C $(@D) config.h
endef

define MINIUPNPD_BUILD_CMDS
	$(MAKE) CC="$(TARGET_CC)" LD="$(TARGET_LD)" \
		CFLAGS="$(TARGET_CFLAGS) -I$(BUILD_DIR)/iptables-$(IPTABLES_VERSION)/include/ -DIPTABLES_143" \
		LIBS="$(TARGET_DIR)/usr/lib/libiptc.so $(TARGET_DIR)/usr/lib/libip4tc.so $(TARGET_DIR)/usr/lib/libip6tc.so" \
		-f Makefile.linux -C $(@D) miniupnpd
endef

define MINIUPNPD_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 package/miniupnpd/miniupnpd.init $(TARGET_DIR)/etc/init.d/miniupnpd
	$(INSTALL) -D $(@D)/miniupnpd $(TARGET_DIR)/usr/sbin/miniupnpd
	-mkdir $(TARGET_DIR)/etc/miniupnpd
#	$(INSTALL) -D package/miniupnpd/miniupnpd.conf -m 0644 $(TARGET_DIR)/etc/miniupnpd/miniupnpd.conf
	$(INSTALL) -D $(@D)/netfilter/iptables_*.sh $(TARGET_DIR)/etc/miniupnpd/
#	$(INSTALL) -D $(@D)/netfilter/ip6tables_*.sh $(TARGET_DIR)/etc/miniupnpd/
endef

$(eval $(call GENTARGETS,package,miniupnpd))
