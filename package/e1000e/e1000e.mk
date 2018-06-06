#############################################################
#
# e1000e
#
#############################################################

E1000E_VERSION = 3.4.1.1
E1000E_SOURCE:=e1000e-$(E1000E_VERSION).tar.gz
E1000E_SITE = http://downloads.sourceforge.net/project/e1000/e1000e%20stable/$(E1000E_VERSION)
E1000E_DEPENDENCIES = linux
E1000E_SUBDIR = src

E1000E_UNINSTALL_STAGING_OPT = --version

E1000E_MAKE_OPT += \
	CC=$(TARGET_CC) \
	LD=$(TARGET_LD) \
	BUILD_KERNEL=$(LINUX_VERSION_PROBED) \
	KSRC=$(LINUX_DIR) \
	RHEL_CODE=0 \
	VERSION_FILE=$(LINUX_DIR)/include/generated/utsrelease.h \
	CONFIG_FILE=$(LINUX_DIR)/include/generated/autoconf.h \
	ARCH=$(KERNEL_ARCH)

define E1000E_CONFIGURE_CMDS
	@echo "No configure"
endef

define E1000E_INSTALL_TARGET_CMDS
	$(INSTALL) -m 644 -D $(@D)/src/e1000e.ko $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/ethernet/intel/e1000e/e1000e.ko
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) -r $(LINUX_VERSION_PROBED)
endef

define E1000E_UNINSTALL_TARGET_CMDS
	rm -rf $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/ethernet/intel/e1000e/e1000e.ko
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) -r $(LINUX_VERSION_PROBED)
endef

$(eval $(call AUTOTARGETS,package,e1000e))
