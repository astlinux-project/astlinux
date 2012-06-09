#############################################################
#
# rhino - Package for Rhino PCI drivers.
#
#############################################################

RHINO_VERSION := 0.99.6b2
RHINO_SOURCE := rhino-linux-$(RHINO_VERSION).tbz2
#RHINO_SOURCE := rhino-linux-current.tbz2
RHINO_SITE := http://files.astlinux.org
#RHINO_SITE := http://downloads.rhinoequipment.com/Rhino%20Downloads/Drivers/DAHDI
RHINO_DIR := $(BUILD_DIR)/rhino-linux-$(RHINO_VERSION)
RHINO_CAT := bzcat
RHINO_TARGET_BINARY := usr/sbin/rhino_ver
RHINO_MODULES := r1t1 rxt1 rcbfx
RHINO_LEGACY_MODULES := r4fxo

RHINO_PREREQS := dahdi-linux
RHINO_CONFIGURE := DAHDI_DIR=$(DAHDI_LINUX_DIR)

$(DL_DIR)/$(RHINO_SOURCE):
	$(WGET) -P $(DL_DIR) $(RHINO_SITE)/$(RHINO_SOURCE)

$(RHINO_DIR)/.source: $(DL_DIR)/$(RHINO_SOURCE)
	$(RHINO_CAT) $(DL_DIR)/$(RHINO_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	touch $@

$(RHINO_DIR)/.patched: $(RHINO_DIR)/.source | $(RHINO_PREREQS)
	toolchain/patch-kernel.sh $(RHINO_DIR) package/rhino/ rhino-\*.patch
	cp -a $(DAHDI_LINUX_DIR)/drivers/dahdi/Module.symvers $(RHINO_DIR)/drivers/rhino/Module.symvers
	touch $@

$(RHINO_DIR)/.built: $(RHINO_DIR)/.patched
	$(MAKE) -C $(RHINO_DIR) \
		HOSTCC=gcc CC=$(TARGET_CC) ARCH=$(KERNEL_ARCH) \
		KVER=$(LINUX_VERSION_PROBED) PWD=$(RHINO_DIR)  \
		KSRC=$(LINUX_DIR) LEGACY_MODULES="$(RHINO_LEGACY_MODULES)" \
		MODULES="$(RHINO_MODULES)" KMOD=$(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED) \
		KINCLUDES=$(STAGING_DIR)/include \
		KINSTDIR=/lib/modules/$(LINUX_VERSION_PROBED)/kernel \
		INSTALL_PREFIX=$(TARGET_DIR) \
		$(RHINO_CONFIGURE) \
		all
	touch $@

$(TARGET_DIR)/$(RHINO_TARGET_BINARY): $(RHINO_DIR)/.built
	$(MAKE) -C $(RHINO_DIR) \
		HOSTCC=gcc CC=$(TARGET_CC) ARCH=$(KERNEL_ARCH) \
		KVER=$(LINUX_VERSION_PROBED) PWD=$(RHINO_DIR) \
		KSRC=$(LINUX_DIR) LEGACY_MODULES="$(RHINO_LEGACY_MODULES)" \
		MODULES="$(RHINO_MODULES)" KMOD=$(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED) \
		KINCLUDES=$(STAGING_DIR)/include \
		KINSTDIR=/lib/modules/$(LINUX_VERSION_PROBED)/kernel \
		INSTALL_PREFIX=$(TARGET_DIR) \
		$(RHINO_CONFIGURE) \
		install
	$(DEPMOD) -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) -r $(LINUX_VERSION_PROBED)
	echo -e "#!/bin/sh\necho \""$(RHINO_VERSION)"\"" > $(TARGET_DIR)/$(RHINO_TARGET_BINARY)
	chmod 755 $(TARGET_DIR)/$(RHINO_TARGET_BINARY)

rhino: $(TARGET_DIR)/$(RHINO_TARGET_BINARY)

rhino-clean:
	rm -rf $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/rhino
	$(DEPMOD) -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) -r $(LINUX_VERSION_PROBED)
	rm -f $(TARGET_DIR)/$(RHINO_TARGET_BINARY)
	rm -f $(RHINO_DIR)/.built

rhino-dirclean:
	rm -rf $(RHINO_DIR)

rhino-source: $(RHINO_DIR)/.patched

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_RHINO)),y)
TARGETS+=rhino
endif
