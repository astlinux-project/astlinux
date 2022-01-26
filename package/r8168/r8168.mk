#############################################################
#
# r8168
#
#############################################################

R8168_VERSION = 8.044.02
R8168_SOURCE:=r8168-$(R8168_VERSION).tar.bz2
R8168_SITE = https://astlinux-project.org/files
R8168_DEPENDENCIES = linux

R8168_UNINSTALL_STAGING_OPT = --version

R8168_MAKE_OPT += \
	CC=$(TARGET_CC) \
	LD=$(TARGET_LD) \
	BASEDIR=$(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED) \
	KERNELDIR=$(LINUX_DIR) \
	KFLAG=2x \
	modules

define R8168_CONFIGURE_CMDS
	@echo "No configure"
endef

define R8168_INSTALL_TARGET_CMDS
	$(INSTALL) -m 644 -D $(@D)/src/r8168.ko $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/ethernet/realtek/r8168.ko
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) $(LINUX_VERSION_PROBED)
endef

define R8168_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/ethernet/realtek/r8168.ko
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) $(LINUX_VERSION_PROBED)
endef

$(eval $(call AUTOTARGETS,package,r8168))
