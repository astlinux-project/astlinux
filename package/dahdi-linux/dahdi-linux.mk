#############################################################
#
# dahdi-linux
#
##############################################################
ifeq ($(BR2_PACKAGE_RHINO),y)
DAHDI_LINUX_VERSION := 2.4.1.1
else
 ifeq ($(BR2_PACKAGE_DAHDI_ZAPHFC),y)
DAHDI_LINUX_VERSION := 2.4.1.1
 else
  ifeq ($(BR2_PACKAGE_WANPIPE),y)
DAHDI_LINUX_VERSION := 2.5.0.2
  else
DAHDI_LINUX_VERSION := 2.6.0
  endif
 endif
endif
DAHDI_LINUX_SOURCE := dahdi-linux-$(DAHDI_LINUX_VERSION).tar.gz
DAHDI_LINUX_SITE := http://downloads.asterisk.org/pub/telephony/dahdi-linux/releases
DAHDI_LINUX_DIR := $(BUILD_DIR)/dahdi-linux-$(DAHDI_LINUX_VERSION)
DAHDI_LINUX_DRIVERS_DIR := $(DAHDI_LINUX_DIR)/drivers/dahdi
DAHDI_LINUX_BINARY := dahdi.ko
DAHDI_LINUX_TARGET_BINARY := etc/udev/rules.d/dahdi.rules
PERLLIBDIR := $(shell eval `perl -V:sitelib`; echo "$$sitelib")
DAHDI_LINUX_PREREQS := linux libusb udev
DAHDI_LINUX_CONFIGURE_ARGS :=
DEPMOD := $(HOST_DIR)/usr/sbin/depmod

$(DL_DIR)/$(DAHDI_LINUX_SOURCE):
	$(WGET) -P $(DL_DIR) $(DAHDI_LINUX_SITE)/$(DAHDI_LINUX_SOURCE)

$(DAHDI_LINUX_DIR)/.source: $(DL_DIR)/$(DAHDI_LINUX_SOURCE)  | $(DAHDI_LINUX_PREREQS)
	zcat $(DL_DIR)/$(DAHDI_LINUX_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
ifeq ($(strip $(BR2_PACKAGE_DAHDI_OSLEC)),y)
	mkdir -p $(DAHDI_LINUX_DIR)/drivers/staging/echo
	cp -a $(BUILD_DIR)/linux-$(LINUX_VERSION)/drivers/staging/echo/* $(DAHDI_LINUX_DIR)/drivers/staging/echo
endif
ifeq ($(strip $(BR2_PACKAGE_DAHDI_ZAPHFC)),y)
	mkdir -p $(DAHDI_LINUX_DIR)/drivers/dahdi/zaphfc
	cp -a package/dahdi-linux/zaphfc/* $(DAHDI_LINUX_DIR)/drivers/dahdi/zaphfc
	toolchain/patch-kernel.sh $(DAHDI_LINUX_DIR) package/dahdi-linux/ zaphfc\*.patch
endif
ifeq ($(strip $(BR2_PACKAGE_DAHDI_HFCS)),y)
	mkdir -p $(DAHDI_LINUX_DIR)/drivers/dahdi/hfcs
	cp -a package/dahdi-linux/hfcs/* $(DAHDI_LINUX_DIR)/drivers/dahdi/hfcs
	toolchain/patch-kernel.sh $(DAHDI_LINUX_DIR) package/dahdi-linux/ hfcs\*.patch
endif
	toolchain/patch-kernel.sh $(DAHDI_LINUX_DIR) package/dahdi-linux/ dahdi-linux\*.patch
	touch $@

$(DAHDI_LINUX_DRIVERS_DIR)/$(DAHDI_LINUX_BINARY): $(DAHDI_LINUX_DIR)/.source
	$(MAKE) -C $(DAHDI_LINUX_DIR) \
		HOSTCC=gcc CC=$(TARGET_CC) ARCH=$(KERNEL_ARCH) \
		KVERS=$(LINUX_VERSION_PROBED) KSRC=$(LINUX_DIR) PWD=$(DAHDI_LINUX_DIR)

$(DAHDI_LINUX_DIR)/include/dahdi/kernel.h: $(DAHDI_LINUX_DIR)/.source

$(STAGING_DIR)/usr/include/dahdi/kernel.h: $(DAHDI_LINUX_DIR)/include/dahdi/kernel.h
	$(MAKE1) -C $(DAHDI_LINUX_DIR) \
		HOSTCC=gcc CC=$(TARGET_CC) ARCH=$(KERNEL_ARCH) \
		DESTDIR=$(STAGING_DIR) KVERS=$(LINUX_VERSION_PROBED) \
		KSRC=$(LINUX_DIR) PWD=$(DAHDI_LINUX_DIR) \
		install-include

$(TARGET_DIR)/$(DAHDI_LINUX_TARGET_BINARY): $(DAHDI_LINUX_DRIVERS_DIR)/$(DAHDI_LINUX_BINARY)
	mkdir -p $(TARGET_DIR)/$(PERLLIBDIR)
	$(MAKE1) -C $(DAHDI_LINUX_DIR) \
		HOSTCC=gcc CC=$(TARGET_CC) ARCH=$(KERNEL_ARCH) \
		DESTDIR=$(TARGET_DIR) KVERS=$(LINUX_VERSION_PROBED) \
		KSRC=$(LINUX_DIR) PWD=$(DAHDI_LINUX_DIR) \
		install
	rm -rf $(TARGET_DIR)/usr/include
	rm -rf $(TARGET_DIR)/$(PERLLIBDIR)
	$(DEPMOD) -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) -r $(LINUX_VERSION_PROBED)

dahdi-linux: $(TARGET_DIR)/$(DAHDI_LINUX_TARGET_BINARY) \
       		$(STAGING_DIR)/usr/include/dahdi/kernel.h

dahdi-linux-source: $(DAHDI_LINUX_DIR)/.source

dahdi-linux-unpack: $(DAHDI_LINUX_DIR)/.configured

dahdi-linux-clean:
	rm -Rf $(STAGING_DIR)/usr/include/dahdi.h
	rm -Rf $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/dahdi
	$(DEPMOD) -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) -r $(LINUX_VERSION_PROBED)
	rm -f $(TARGET_DIR)/$(DAHDI_LINUX_TARGET_BINARY)

dahdi-linux-dirclean:
	rm -rf $(DAHDI_LINUX_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_DAHDI_LINUX)),y)
TARGETS+=dahdi-linux
endif

