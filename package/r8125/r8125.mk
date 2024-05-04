#############################################################
#
# r8125
#
#############################################################

R8125_VERSION = 9.013.02
R8125_SOURCE:=r8125-$(R8125_VERSION).tar.bz2
R8125_SITE = https://astlinux-project.org/files
R8125_DEPENDENCIES = linux

R8125_UNINSTALL_STAGING_OPT = --version

R8125_MAKE_OPT += \
	CC=$(TARGET_CC) \
	LD=$(TARGET_LD) \
	BASEDIR=$(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED) \
	KERNELDIR=$(LINUX_DIR) \
	KFLAG=2x \
	modules

define R8125_CONFIGURE_CMDS
	@echo "No configure"
endef

define R8125_INSTALL_TARGET_CMDS
	$(INSTALL) -m 644 -D $(@D)/src/r8125.ko $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/ethernet/realtek/r8125.ko
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) $(LINUX_VERSION_PROBED)
endef

define R8125_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/ethernet/realtek/r8125.ko
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) $(LINUX_VERSION_PROBED)
endef

$(eval $(call AUTOTARGETS,package,r8125))
