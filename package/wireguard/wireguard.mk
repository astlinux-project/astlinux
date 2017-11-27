################################################################################
#
# wireguard
#
################################################################################

WIREGUARD_VERSION = 0.0.20171127
WIREGUARD_SITE = https://git.zx2c4.com/WireGuard/snapshot
WIREGUARD_SOURCE = WireGuard-$(WIREGUARD_VERSION).tar.xz
WIREGUARD_DEPENDENCIES = host-pkg-config linux libmnl

WIREGUARD_MAKE_OPTS = \
	WITH_BASHCOMPLETION=no \
	WITH_WGQUICK=no \
	WITH_SYSTEMDUNITS=no \
	KERNELDIR=$(LINUX_DIR)

define WIREGUARD_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(TARGET_CONFIGURE_OPTS) $(MAKE) $(WIREGUARD_MAKE_OPTS) \
		-C $(@D)/src tools module
endef

define WIREGUARD_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/src/tools/wg $(TARGET_DIR)/usr/bin/wg
	$(INSTALL) -m 0644 -D $(@D)/src/wireguard.ko $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/net/wireguard/wireguard.ko
	$(INSTALL) -m 0755 -D package/wireguard/wireguard-monitor $(TARGET_DIR)/usr/sbin/wireguard-monitor
	$(INSTALL) -m 0755 -D package/wireguard/wireguard.init $(TARGET_DIR)/etc/init.d/wireguard
	ln -sf /tmp/etc/wireguard $(TARGET_DIR)/etc/wireguard
	ln -sf ../../init.d/wireguard $(TARGET_DIR)/etc/runlevels/default/S31wireguard
	ln -sf ../../init.d/wireguard $(TARGET_DIR)/etc/runlevels/default/K20wireguard
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) -r $(LINUX_VERSION_PROBED)
endef

define WIREGUARD_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/wg
	rm -f $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/net/wireguard/wireguard.ko
	rm -f $(TARGET_DIR)/usr/sbin/wireguard-monitor
	rm -f $(TARGET_DIR)/etc/init.d/wireguard
	rm -f $(TARGET_DIR)/etc/wireguard
	rm -f $(TARGET_DIR)/etc/runlevels/default/S31wireguard
	rm -f $(TARGET_DIR)/etc/runlevels/default/K20wireguard
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) -r $(LINUX_VERSION_PROBED)
endef

$(eval $(call GENTARGETS,package,wireguard))
