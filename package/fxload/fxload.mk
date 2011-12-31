#############################################################
#
# fxload
#
#############################################################
FXLOAD_VERSION:=2008_10_13
FXLOAD_SOURCE:=fxload-$(FXLOAD_VERSION).tar.gz
FXLOAD_SITE:=http://$(BR2_SOURCEFORGE_MIRROR).dl.sourceforge.net/sourceforge/linux-hotplug/fxload/$(FXLOAD_VERSION)
FXLOAD_DIR:=$(BUILD_DIR)/fxload-$(FXLOAD_VERSION)
FXLOAD_CAT:=zcat
FXLOAD_BINARY:=fxload
FXLOAD_TARGET_BINARY:=usr/sbin/fxload

$(DL_DIR)/$(FXLOAD_SOURCE):
	$(WGET) -P $(DL_DIR) $(FXLOAD_SITE)/$(FXLOAD_SOURCE)

$(FXLOAD_DIR)/.unpacked: $(DL_DIR)/$(FXLOAD_SOURCE)
	$(FXLOAD_CAT) $(DL_DIR)/$(FXLOAD_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	touch $@

$(FXLOAD_DIR)/$(FXLOAD_BINARY): $(FXLOAD_DIR)/.unpacked
	$(TARGET_CONFIGURE_OPTS) $(MAKE) CC=$(TARGET_CC) -C $(FXLOAD_DIR)

$(TARGET_DIR)/$(FXLOAD_TARGET_BINARY): $(FXLOAD_DIR)/$(FXLOAD_BINARY)
	$(INSTALL) -D $(FXLOAD_DIR)/$(FXLOAD_BINARY) $(TARGET_DIR)/$(FXLOAD_TARGET_BINARY)

fxload: $(TARGET_DIR)/$(FXLOAD_TARGET_BINARY)

fxload-source: $(FXLOAD_DIR)/.unpacked

fxload-clean:
	rm -f $(TARGET_DIR)/$(FXLOAD_TARGET_BINARY)
	-$(MAKE) -C $(FXLOAD_DIR) clean

fxload-dirclean:
	rm -rf $(FXLOAD_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_FXLOAD)),y)
TARGETS+=fxload
endif
