################################################################################
#
# wireguard
#
################################################################################

WIREGUARD_VERSION = 1.0.20210424
WIREGUARD_SITE = https://git.zx2c4.com/wireguard-linux-compat/snapshot
WIREGUARD_SOURCE = wireguard-linux-compat-$(WIREGUARD_VERSION).tar.xz
WIREGUARD_DEPENDENCIES = linux

WIREGUARD_MAKE_OPTS = \
	CC=$(TARGET_CC) \
	LD=$(TARGET_LD) \
	KERNELRELEASE=$(LINUX_VERSION_PROBED) \
	KERNELDIR=$(LINUX_DIR)

define WIREGUARD_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(TARGET_CONFIGURE_OPTS) $(MAKE) $(WIREGUARD_MAKE_OPTS) \
		-C $(@D)/src module
endef

define WIREGUARD_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0644 -D $(@D)/src/wireguard.ko $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/wireguard/wireguard.ko
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) $(LINUX_VERSION_PROBED)
endef

define WIREGUARD_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/wireguard/wireguard.ko
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) $(LINUX_VERSION_PROBED)
endef

$(eval $(call GENTARGETS,package,wireguard))
