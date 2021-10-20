#############################################################
#
# igb
#
#############################################################

IGB_VERSION = 5.8.5
IGB_SOURCE:=igb-$(IGB_VERSION).tar.gz
IGB_SITE = http://downloads.sourceforge.net/project/e1000/igb%20stable/$(IGB_VERSION)
IGB_DEPENDENCIES = linux
IGB_SUBDIR = src

IGB_UNINSTALL_STAGING_OPT = --version

IGB_MAKE_OPT += \
	CC=$(TARGET_CC) \
	LD=$(TARGET_LD) \
	BUILD_KERNEL=$(LINUX_VERSION_PROBED) \
	KSRC=$(LINUX_DIR) \
	VERSION_FILE=$(LINUX_DIR)/include/generated/utsrelease.h \
	CONFIG_FILE=$(LINUX_DIR)/include/generated/autoconf.h \
	ARCH=$(KERNEL_ARCH)

define IGB_CONFIGURE_CMDS
	@echo "No configure"
endef

define IGB_INSTALL_TARGET_CMDS
	$(INSTALL) -m 644 -D $(@D)/src/igb.ko $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/ethernet/intel/igb/igb.ko
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) $(LINUX_VERSION_PROBED)
endef

define IGB_UNINSTALL_TARGET_CMDS
	rm -rf $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/ethernet/intel/igb/igb.ko
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) $(LINUX_VERSION_PROBED)
endef

$(eval $(call AUTOTARGETS,package,igb))
