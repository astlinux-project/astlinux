#############################################################
#
# dahdi-linux
#
##############################################################
DAHDI_LINUX_VERSION := 3.2.0
DAHDI_LINUX_SOURCE := dahdi-linux-$(DAHDI_LINUX_VERSION).tar.gz
DAHDI_LINUX_SITE := https://downloads.asterisk.org/pub/telephony/dahdi-linux/releases
DAHDI_LINUX_DIR := $(BUILD_DIR)/dahdi-linux-$(DAHDI_LINUX_VERSION)
DAHDI_LINUX_DRIVERS_DIR := $(DAHDI_LINUX_DIR)/drivers/dahdi
DAHDI_LINUX_BINARY := dahdi.ko
DAHDI_LINUX_TARGET_BINARY := usr/share/dahdi/XppConfig.pm
PERLLIBDIR := /usr/local/share/perl
DAHDI_LINUX_PREREQS := linux libusb libusb-compat udev
DAHDI_LINUX_CONFIGURE_ARGS :=
DEPMOD := $(HOST_DIR)/usr/sbin/depmod

# $(call ndots start,end,dotted-string)
dot:=.
empty:=
space:=$(empty) $(empty)
ndots = $(subst $(space),$(dot),$(wordlist $(1),$(2),$(subst $(dot),$(space),$3)))
##
DAHDI_LINUX_VERSION_SINGLE := $(call ndots,1,1,$(DAHDI_LINUX_VERSION))
DAHDI_LINUX_VERSION_TUPLE := $(call ndots,1,2,$(DAHDI_LINUX_VERSION))

$(DL_DIR)/$(DAHDI_LINUX_SOURCE):
	$(WGET) -P $(DL_DIR) $(DAHDI_LINUX_SITE)/$(DAHDI_LINUX_SOURCE)

$(DAHDI_LINUX_DIR)/.source: $(DL_DIR)/$(DAHDI_LINUX_SOURCE)  | $(DAHDI_LINUX_PREREQS)
	zcat $(DL_DIR)/$(DAHDI_LINUX_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
ifeq ($(strip $(BR2_PACKAGE_DAHDI_OSLEC)),y)
	mkdir -p $(DAHDI_LINUX_DIR)/drivers/staging/echo
	cp -a $(BUILD_DIR)/linux-$(LINUX_VERSION)/drivers/misc/echo/* $(DAHDI_LINUX_DIR)/drivers/staging/echo
	toolchain/patch-kernel.sh $(DAHDI_LINUX_DIR) package/dahdi-linux/ oslec-$(DAHDI_LINUX_VERSION_TUPLE)-\*.patch
endif
	toolchain/patch-kernel.sh $(DAHDI_LINUX_DIR) package/dahdi-linux/ dahdi-linux-$(DAHDI_LINUX_VERSION_SINGLE)-\*.patch
	toolchain/patch-kernel.sh $(DAHDI_LINUX_DIR) package/dahdi-linux/ dahdi-linux-$(DAHDI_LINUX_VERSION_TUPLE)-\*.patch
	touch $@

$(DAHDI_LINUX_DRIVERS_DIR)/$(DAHDI_LINUX_BINARY): $(DAHDI_LINUX_DIR)/.source
	$(MAKE) -C $(DAHDI_LINUX_DIR) \
		HOSTCC=gcc CC=$(TARGET_CC) LD=$(TARGET_LD) ARCH=$(KERNEL_ARCH) \
		KVERS=$(LINUX_VERSION_PROBED) KSRC=$(LINUX_DIR) PWD=$(DAHDI_LINUX_DIR)

$(DAHDI_LINUX_DIR)/include/dahdi/kernel.h: $(DAHDI_LINUX_DIR)/.source

$(STAGING_DIR)/usr/include/dahdi/kernel.h: $(DAHDI_LINUX_DIR)/include/dahdi/kernel.h
	$(MAKE1) -C $(DAHDI_LINUX_DIR) \
		HOSTCC=gcc CC=$(TARGET_CC) LD=$(TARGET_LD) ARCH=$(KERNEL_ARCH) \
		DESTDIR=$(STAGING_DIR) KVERS=$(LINUX_VERSION_PROBED) \
		KSRC=$(LINUX_DIR) PWD=$(DAHDI_LINUX_DIR) \
		install-include

$(TARGET_DIR)/$(DAHDI_LINUX_TARGET_BINARY): $(DAHDI_LINUX_DRIVERS_DIR)/$(DAHDI_LINUX_BINARY)
	mkdir -p $(TARGET_DIR)$(PERLLIBDIR)
	$(MAKE1) -C $(DAHDI_LINUX_DIR) \
		HOSTCC=gcc CC=$(TARGET_CC) LD=$(TARGET_LD) ARCH=$(KERNEL_ARCH) \
		DESTDIR=$(TARGET_DIR) KVERS=$(LINUX_VERSION_PROBED) \
		KSRC=$(LINUX_DIR) PWD=$(DAHDI_LINUX_DIR) \
		PERLLIBDIR=$(PERLLIBDIR) \
		install
	rm -rf $(TARGET_DIR)/usr/include
ifeq ($(BR2_PACKAGE_DAHDI_NO_CARD_FIRMWARE),y)
	find $(TARGET_DIR)/lib/firmware/ -type f -name "*dahdi-fw-*" -print0 | xargs -0 rm -f
endif
	$(DEPMOD) -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) $(LINUX_VERSION_PROBED)

dahdi-linux: $(TARGET_DIR)/$(DAHDI_LINUX_TARGET_BINARY) \
       		$(STAGING_DIR)/usr/include/dahdi/kernel.h

dahdi-linux-source: $(DAHDI_LINUX_DIR)/.source

dahdi-linux-unpack: $(DAHDI_LINUX_DIR)/.configured

dahdi-linux-clean:
	rm -Rf $(STAGING_DIR)/usr/include/dahdi.h
	rm -Rf $(TARGET_DIR)/lib/modules/$(LINUX_VERSION_PROBED)/dahdi
	$(DEPMOD) -ae -F $(LINUX_DIR)/System.map -b $(TARGET_DIR) $(LINUX_VERSION_PROBED)
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

