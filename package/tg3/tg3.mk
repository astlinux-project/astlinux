#############################################################
#
# tg3
#
#############################################################

TG3_VERSION = 3.137k
TG3_SOURCE:=tg3-$(TG3_VERSION).tar.gz
TG3_SITE = https://astlinux-project.org/files
TG3_DEPENDENCIES = linux

TG3_UNINSTALL_STAGING_OPT = --version

TG3_MAKE_OPT += \
	CC=$(TARGET_CC) \
	LD=$(TARGET_LD) \
	KVER=$(LINUX_VERSION_PROBED) \
	BCMPROC=$(KERNEL_ARCH) \
	BCMCFGDIR=$(LINUX_DIR) \
	BCMMODDIR=$(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/ethernet/broadcom \
	BCMSRCDIR=$(LINUX_DIR) \
	ARCH=$(KERNEL_ARCH)

define TG3_CONFIGURE_CMDS
	@echo "No configure"
endef

define TG3_INSTALL_TARGET_CMDS
	$(INSTALL) -m 644 -D $(@D)/tg3.ko $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/ethernet/broadcom/tg3.ko
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) $(LINUX_VERSION_PROBED)
endef

define TG3_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/ethernet/broadcom/tg3.ko
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) $(LINUX_VERSION_PROBED)
endef

$(eval $(call AUTOTARGETS,package,tg3))
