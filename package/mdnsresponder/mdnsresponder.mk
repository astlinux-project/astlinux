#############################################################
#
# mdnsresponder
#
##############################################################
MDNSRESPONDER_VERSION := 107.6
MDNSRESPONDER_SOURCE := mDNSResponder-$(MDNSRESPONDER_VERSION).tar.gz
MDNSRESPONDER_SITE := http://www.opensource.apple.com/tarballs
MDNSRESPONDER_DIR := $(BUILD_DIR)/mDNSResponder-$(MDNSRESPONDER_VERSION)
MDNSRESPONDER_BINARY := mDNSPosix/build/prod/mDNSProxyResponderPosix
MDNSRESPONDER_TARGET_BINARY := usr/sbin/mDNSProxyResponderPosix

$(DL_DIR)/$(MDNSRESPONDER_SOURCE):
	$(WGET) -P $(DL_DIR) $(MDNSRESPONDER_SITE)/$(MDNSRESPONDER_SOURCE)

$(MDNSRESPONDER_DIR)/.source: $(DL_DIR)/$(MDNSRESPONDER_SOURCE)
	zcat $(DL_DIR)/$(MDNSRESPONDER_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	toolchain/patch-kernel.sh $(MDNSRESPONDER_DIR) package/mdnsresponder/ mdnsresponder\*.patch
	touch $@

$(MDNSRESPONDER_DIR)/.configured: $(MDNSRESPONDER_DIR)/.source
	touch $@

$(MDNSRESPONDER_DIR)/$(MDNSRESPONDER_BINARY): $(MDNSRESPONDER_DIR)/.configured
	$(MAKE) CC=$(TARGET_CC) os="linux" LD="$(TARGET_CC) -shared" LOCALBASE="/usr" -C $(MDNSRESPONDER_DIR)/mDNSPosix

$(TARGET_DIR)/$(MDNSRESPONDER_TARGET_BINARY): $(MDNSRESPONDER_DIR)/$(MDNSRESPONDER_BINARY)
	$(INSTALL) -s -D -m 0755 $(MDNSRESPONDER_DIR)/$(MDNSRESPONDER_BINARY) $(TARGET_DIR)/$(MDNSRESPONDER_TARGET_BINARY)
	$(INSTALL) -D -m 0755 package/mdnsresponder/mdns.init $(TARGET_DIR)/etc/init.d/mdns

mdnsresponder: $(TARGET_DIR)/$(MDNSRESPONDER_TARGET_BINARY)

mdnsresponder-source: $(MDNSRESPONDER_DIR)/.source

mdnsresponder-clean:
	rm -rf $(TARGET_DIR)/$(MDNSRESPONDER_TARGET_BINARY)
	rm -rf $(TARGET_DIR)/etc/init.d/mdns
	-$(MAKE) -C $(MDNSRESPONDER_DIR)/mDNSPosix os=linux clean

mdnsresponder-dirclean:
	rm -rf $(MDNSRESPONDER_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_MDNSRESPONDER)),y)
TARGETS+=mdnsresponder
endif
