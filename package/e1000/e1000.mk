#############################################################
#
# e1000
#
#############################################################

E1000_VERSION = 8.0.35
E1000_SOURCE:=e1000-$(E1000_VERSION).tar.gz
E1000_SITE = http://$(BR2_SOURCEFORGE_MIRROR).dl.sourceforge.net/project/e1000/e1000%20stable/$(E1000_VERSION)
E1000_DEPENDENCIES = linux
E1000_SUBDIR = src

E1000_UNINSTALL_STAGING_OPT = --version

E1000_MAKE_OPT += \
	CC=$(TARGET_CC) \
	LD=$(TARGET_LD) \
	BUILD_KERNEL=$(LINUX_VERSION_PROBED) \
	KSRC=$(LINUX_DIR) \
	VERSION_FILE=$(LINUX_DIR)/include/generated/utsrelease.h \
	CONFIG_FILE=$(LINUX_DIR)/include/generated/autoconf.h \
	ARCH=$(KERNEL_ARCH)

define E1000_CONFIGURE_CMDS
	@echo "No configure"
endef

define E1000_INSTALL_TARGET_CMDS
	$(INSTALL) -m 644 -D $(@D)/src/e1000.ko $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/e1000/e1000.ko
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) -r $(LINUX_VERSION_PROBED)
endef

define E1000_UNINSTALL_TARGET_CMDS
	rm -rf $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/kernel/drivers/net/e1000/e1000.ko
	$(HOST_DIR)/usr/sbin/depmod -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) -r $(LINUX_VERSION_PROBED)
endef

$(eval $(call AUTOTARGETS,package,e1000))
