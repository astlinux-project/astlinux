#############################################################
#
# miniupnpd
#
#############################################################

MINIUPNPD_VERSION = 1.7
MINIUPNPD_SOURCE = miniupnpd-$(MINIUPNPD_VERSION).tar.gz
MINIUPNPD_SITE = http://miniupnp.free.fr/files
MINIUPNPD_DEPENDENCIES = linux iptables

define MINIUPNPD_IPTABLES_PATH_FIX
	$(SED) 's:/sbin/iptables:/usr/sbin/iptables:g' \
	    -e 's:/sbin/ip6tables:/usr/sbin/ip6tables:g' $(@D)/netfilter/ip*.sh
endef

MINIUPNPD_POST_PATCH_HOOKS += MINIUPNPD_IPTABLES_PATH_FIX

define MINIUPNPD_CONFIGURE_CMDS
# add this to make for IPv6... CONFIG_OPTIONS="--ipv6"
	echo "$(LINUX_VERSION_PROBED)" >$(@D)/os.astlinux
	$(MAKE) CC="$(TARGET_CC)" LD="$(TARGET_LD)" CFLAGS="$(TARGET_CFLAGS)" \
		-f Makefile.linux -C $(@D) config.h
endef

define MINIUPNPD_BUILD_CMDS
	$(MAKE) CC="$(TARGET_CC)" LD="$(TARGET_LD)" \
		CFLAGS="$(TARGET_CFLAGS) -I$(BUILD_DIR)/iptables-$(IPTABLES_VERSION)/include/ -DIPTABLES_143" \
		LIBS="$(STAGING_DIR)/usr/lib/libiptc.so $(STAGING_DIR)/usr/lib/libip4tc.so $(STAGING_DIR)/usr/lib/libip6tc.so" \
		-f Makefile.linux -C $(@D) miniupnpd
endef

define MINIUPNPD_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 package/miniupnpd/miniupnpd.init $(TARGET_DIR)/etc/init.d/miniupnpd
	$(INSTALL) -D $(@D)/miniupnpd $(TARGET_DIR)/usr/sbin/miniupnpd
	-mkdir $(TARGET_DIR)/etc/miniupnpd
	$(INSTALL) -D $(@D)/netfilter/iptables_*.sh $(TARGET_DIR)/etc/miniupnpd/
#	$(INSTALL) -D $(@D)/netfilter/ip6tables_*.sh $(TARGET_DIR)/etc/miniupnpd/
	ln -snf ../../init.d/miniupnpd $(TARGET_DIR)/etc/runlevels/default/S54miniupnpd
	ln -snf ../../init.d/miniupnpd $(TARGET_DIR)/etc/runlevels/default/K09miniupnpd
endef

define MINIUPNPD_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/etc/init.d/miniupnpd
	rm -f $(TARGET_DIR)/usr/sbin/miniupnpd
	rm -rf $(TARGET_DIR)/etc/miniupnpd/
	rm -f $(TARGET_DIR)/etc/runlevels/default/S54miniupnpd
	rm -f $(TARGET_DIR)/etc/runlevels/default/K09miniupnpd
endef

$(eval $(call GENTARGETS,package,miniupnpd))
