################################################################################
#
# wireguard-tools
#
################################################################################

WIREGUARD_TOOLS_VERSION = 1.0.20210223
WIREGUARD_TOOLS_SITE = https://git.zx2c4.com/wireguard-tools/snapshot
WIREGUARD_TOOLS_SOURCE = wireguard-tools-$(WIREGUARD_TOOLS_VERSION).tar.xz
WIREGUARD_TOOLS_DEPENDENCIES = host-pkg-config

WIREGUARD_TOOLS_MAKE_OPTS = \
	CC=$(TARGET_CC) \
	LD=$(TARGET_LD) \
	WITH_BASHCOMPLETION=no \
	WITH_WGQUICK=no \
	WITH_SYSTEMDUNITS=no

define WIREGUARD_TOOLS_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(TARGET_CONFIGURE_OPTS) $(MAKE) $(WIREGUARD_TOOLS_MAKE_OPTS) \
		-C $(@D)/src wg
endef

define WIREGUARD_TOOLS_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/src/wg $(TARGET_DIR)/usr/bin/wg
	$(INSTALL) -m 0755 -D package/wireguard-tools/wireguard-monitor $(TARGET_DIR)/usr/sbin/wireguard-monitor
	$(INSTALL) -m 0755 -D package/wireguard-tools/wireguard-mobile-client $(TARGET_DIR)/usr/sbin/wireguard-mobile-client
	$(INSTALL) -m 0755 -D package/wireguard-tools/wireguard.init $(TARGET_DIR)/etc/init.d/wireguard
	ln -sf /tmp/etc/wireguard $(TARGET_DIR)/etc/wireguard
	ln -sf ../../init.d/wireguard $(TARGET_DIR)/etc/runlevels/default/S31wireguard
	ln -sf ../../init.d/wireguard $(TARGET_DIR)/etc/runlevels/default/K20wireguard
endef

define WIREGUARD_TOOLS_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/wg
	rm -f $(TARGET_DIR)/usr/sbin/wireguard-monitor
	rm -f $(TARGET_DIR)/usr/sbin/wireguard-mobile-client
	rm -f $(TARGET_DIR)/etc/init.d/wireguard
	rm -f $(TARGET_DIR)/etc/wireguard
	rm -f $(TARGET_DIR)/etc/runlevels/default/S31wireguard
	rm -f $(TARGET_DIR)/etc/runlevels/default/K20wireguard
endef

$(eval $(call GENTARGETS,package,wireguard-tools))
