#############################################################
#
# miniupnpd
#
#############################################################

MINIUPNPD_VERSION = 2.0.20180203
MINIUPNPD_SOURCE = miniupnpd-$(MINIUPNPD_VERSION).tar.gz
MINIUPNPD_SITE = http://miniupnp.free.fr/files
MINIUPNPD_DEPENDENCIES = host-pkg-config linux iptables openssl util-linux

define MINIUPNPD_IPTABLES_PATH_FIX
	$(SED) 's:/sbin/iptables:/usr/sbin/iptables:g' \
	    -e 's:/sbin/ip6tables:/usr/sbin/ip6tables:g' $(@D)/netfilter/ip*.sh
	$(SED) 's:#define ENABLE_PORT_TRIGGERING:/*&*/:' $(@D)/genconfig.sh
endef

MINIUPNPD_POST_PATCH_HOOKS += MINIUPNPD_IPTABLES_PATH_FIX

define MINIUPNPD_CONFIGURE_CMDS
	echo "$(LINUX_VERSION_PROBED)" >$(@D)/os.astlinux
endef

define MINIUPNPD_BUILD_CMDS
	$(TARGET_CONFIGURE_OPTS) \
	CONFIG_OPTIONS="--leasefile --portinuse --vendorcfg --disable-pppconn" \
	$(TARGET_MAKE_ENV) $(MAKE) -f Makefile.linux -C $(@D) miniupnpd
endef

define MINIUPNPD_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 package/miniupnpd/miniupnpd.init $(TARGET_DIR)/etc/init.d/miniupnpd
	$(INSTALL) -D $(@D)/miniupnpd $(TARGET_DIR)/usr/sbin/miniupnpd
#	-mkdir $(TARGET_DIR)/etc/miniupnpd
#	$(INSTALL) -D $(@D)/netfilter/iptables_*.sh $(TARGET_DIR)/etc/miniupnpd/
#	$(INSTALL) -D $(@D)/netfilter/ip6tables_*.sh $(TARGET_DIR)/etc/miniupnpd/
	ln -sf /tmp/etc/miniupnpd.conf $(TARGET_DIR)/etc/miniupnpd.conf
	ln -sf ../../init.d/miniupnpd $(TARGET_DIR)/etc/runlevels/default/S54miniupnpd
	ln -sf ../../init.d/miniupnpd $(TARGET_DIR)/etc/runlevels/default/K09miniupnpd
endef

define MINIUPNPD_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/etc/init.d/miniupnpd
	rm -f $(TARGET_DIR)/usr/sbin/miniupnpd
	rm -rf $(TARGET_DIR)/etc/miniupnpd/
	rm -f $(TARGET_DIR)/etc/miniupnpd.conf
	rm -f $(TARGET_DIR)/etc/runlevels/default/S54miniupnpd
	rm -f $(TARGET_DIR)/etc/runlevels/default/K09miniupnpd
endef

$(eval $(call GENTARGETS,package,miniupnpd))
